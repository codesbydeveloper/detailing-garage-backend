<?php

namespace App\Services;

use App\Models\SecurityPin;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class SecurityPinService
{
    public function __construct(private ActivityLogService $activity) {}

    /**
     * @return array{success: bool, message: string, code?: string, expires_at?: string}
     */
    public function verify(User $user, string $pin): array
    {
        $outcome = DB::transaction(function () use ($user, $pin) {
            $record = SecurityPin::query()->where('user_id', $user->id)->lockForUpdate()->first();

            if (! $record) {
                return ['type' => 'missing'];
            }

            if ($record->locked_until && $record->locked_until->isFuture()) {
                $this->activity->log('PIN_LOCKED', 'security', 'PIN verification blocked because the account is locked', $record, null, null, 'failed', $user);

                return ['type' => 'locked', 'locked_until' => $record->locked_until?->toIso8601String()];
            }

            if ($record->locked_until && $record->locked_until->isPast()) {
                $record->failed_attempts = 0;
                $record->locked_until = null;
            }

            if (! Hash::check($pin, $record->pin_hash)) {
                $record->failed_attempts++;
                $locked = $record->failed_attempts >= (int) config('security.pin_max_attempts');

                if ($locked) {
                    $record->locked_until = now()->addMinutes((int) config('security.pin_lock_minutes'));
                }

                $record->save();

                $this->activity->log(
                    $locked ? 'PIN_LOCKED' : 'PIN_FAILED',
                    'security',
                    $locked ? 'PIN locked after too many failed attempts' : 'Incorrect security PIN',
                    $record,
                    null,
                    ['failed_attempts' => $record->failed_attempts],
                    'failed',
                    $user,
                );

                return [
                    'type' => $locked ? 'locked' : 'invalid',
                    'locked_until' => $locked ? $record->locked_until?->toIso8601String() : null,
                ];
            }

            $record->forceFill([
                'failed_attempts' => 0,
                'locked_until' => null,
            ])->save();

            $expiresAt = now()->addMinutes((int) config('security.pin_session_minutes'));

            Cache::put($this->cacheKey($user), [
                'expires_at' => $expiresAt->toIso8601String(),
                'pin_updated_at' => $record->pin_updated_at?->toIso8601String(),
            ], now()->addHours(12));

            $this->activity->log('PIN_VERIFIED', 'security', 'Security PIN verified', $record, null, ['expires_at' => $expiresAt->toIso8601String()], 'success', $user);

            return [
                'type' => 'ok',
                'expires_at' => $expiresAt->toIso8601String(),
            ];
        });

        return match ($outcome['type']) {
            'ok' => [
                'success' => true,
                'verified' => true,
                'message' => 'PIN verified',
                'expires_at' => $outcome['expires_at'],
            ],
            'locked' => [
                'success' => false,
                'code' => 'PIN_LOCKED',
                'message' => 'Too many failed attempts. Try again later.',
                'locked_until' => $outcome['locked_until'] ?? null,
            ],
            'missing' => [
                'success' => false,
                'code' => 'PIN_REQUIRED',
                'message' => 'Security PIN is not configured for this account.',
            ],
            default => [
                'success' => false,
                'code' => 'PIN_INVALID',
                'message' => 'Invalid PIN',
            ],
        };
    }

    public function change(User $user, string $currentPin, string $newPin): void
    {
        $changed = DB::transaction(function () use ($user, $currentPin, $newPin) {
            $record = SecurityPin::query()->where('user_id', $user->id)->lockForUpdate()->first();

            if (! $record || ! Hash::check($currentPin, $record->pin_hash)) {
                if ($record) {
                    $record->failed_attempts++;
                    $record->save();
                }

                $this->activity->log('PIN_FAILED', 'security', 'PIN change rejected', $record, null, null, 'failed', $user);

                return false;
            }

            $record->forceFill([
                'pin_hash' => Hash::make($newPin),
                'pin_updated_at' => now(),
                'failed_attempts' => 0,
                'locked_until' => null,
            ])->save();

            $this->forget($user);

            $this->activity->log(
                'PIN_CHANGED',
                'security',
                'Security PIN changed',
                $record,
                null,
                ['pin_updated_at' => $record->pin_updated_at?->toIso8601String()],
                'success',
                $user,
            );

            return true;
        });

        if (! $changed) {
            throw ValidationException::withMessages([
                'current_pin' => ['The current PIN is incorrect.'],
            ]);
        }
    }

    public function forget(User $user): void
    {
        Cache::forget($this->cacheKey($user));
    }

    public function isVerified(User $user): bool
    {
        return $this->sessionState($user) === 'valid';
    }

    public function sessionState(User $user): string
    {
        $payload = Cache::get($this->cacheKey($user));

        if (! is_array($payload) || empty($payload['expires_at'])) {
            return 'missing';
        }

        $record = $user->relationLoaded('securityPin') ? $user->securityPin : $user->securityPin()->first();
        $stored = $payload['pin_updated_at'] ?? null;
        $current = $record?->pin_updated_at;

        if ($stored === null || $current === null) {
            if ($stored !== null || $current !== null) {
                return 'missing';
            }
        } elseif (! $current->equalTo(Carbon::parse($stored))) {
            return 'missing';
        }

        if (now()->greaterThan(Carbon::parse($payload['expires_at']))) {
            return 'expired';
        }

        return 'valid';
    }

    public function status(User $user): array
    {
        $record = $user->securityPin;
        $payload = Cache::get($this->cacheKey($user));
        $state = $this->sessionState($user);

        return [
            'pin_configured' => $record !== null,
            'verified' => $state === 'valid',
            'expires_at' => $state === 'valid' && is_array($payload) ? $payload['expires_at'] : null,
            'locked' => (bool) ($record?->locked_until?->isFuture()),
            'locked_until' => $record?->locked_until?->toIso8601String(),
            'failed_attempts' => $failed = (int) ($record->failed_attempts ?? 0),
            'remaining_attempts' => max(0, (int) config('security.pin_max_attempts') - $failed),
            'min_length' => 4,
            'max_length' => 6,
            'pin_length' => 6,
            'ttl_minutes' => (int) config('security.pin_session_minutes'),
            'protected_paths' => [
                '/dashboard',
                '/reports',
                '/salary',
                '/expenses/office',
                '/expenses/personal',
                '/expenses/vendors',
                '/staff',
                '/activity-logs',
                '/settings',
            ],
        ];
    }

    public function cacheKey(User $user): string
    {
        $token = $user->currentAccessToken();
        $tokenId = $token instanceof PersonalAccessToken ? (string) $token->getKey() : 'current';

        return 'pin_session:'.$user->id.':'.$tokenId;
    }
}
