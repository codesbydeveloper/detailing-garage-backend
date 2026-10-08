<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityLogService
{
    private const SENSITIVE = [
        'password',
        'password_confirmation',
        'current_password',
        'pin',
        'current_pin',
        'new_pin',
        'new_pin_confirmation',
        'pin_hash',
        'token',
        'remember_token',
        'api_key',
        'secret',
        'access_token',
        'plain_text_token',
        'authorization',
    ];

    public function log(
        string $action,
        string $module,
        string $description,
        ?Model $entity = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        string $status = 'success',
        ?User $user = null,
    ): ActivityLog {
        $actor = $user ?? auth()->user();

        return ActivityLog::query()->create([
            'user_id' => $actor?->id,
            'action' => $action,
            'module' => $module,
            'entity_type' => $entity ? class_basename($entity) : null,
            'entity_id' => $entity?->getKey(),
            'description' => $description,
            'old_values' => $this->sanitize($oldValues),
            'new_values' => $this->sanitize($newValues),
            'ip_address' => $this->ip(),
            'user_agent' => $this->userAgent(),
            'status' => $status,
        ]);
    }

    public function snapshot(Model $model): array
    {
        $data = $model->attributesToArray();

        foreach (array_merge($model->getHidden(), ['password', 'pin_hash', 'remember_token']) as $hidden) {
            unset($data[$hidden]);
        }

        return $this->sanitize($data) ?? [];
    }

    /**
     * @return array{0: ?array, 1: ?array}
     */
    public function changed(Model $model): array
    {
        $changes = $model->getChanges();
        unset($changes['updated_at'], $changes['created_at'], $changes['updated_by']);

        if ($changes === []) {
            return [null, null];
        }

        $old = [];
        $new = [];

        foreach ($changes as $key => $value) {
            if ($this->isSensitive((string) $key)) {
                continue;
            }

            $old[$key] = $model->getOriginal($key);
            $new[$key] = $value;
        }

        $old = $this->sanitize($old);
        $new = $this->sanitize($new);

        if ($old === [] && $new === []) {
            return [null, null];
        }

        return [$old, $new];
    }

    private function sanitize(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        $clean = [];

        foreach ($values as $key => $value) {
            if ($this->isSensitive((string) $key)) {
                continue;
            }

            if ($value instanceof \DateTimeInterface) {
                $value = $value->format(DATE_ATOM);
            }

            $clean[$key] = is_array($value) ? $this->sanitize($value) : $value;
        }

        return $clean;
    }

    private function isSensitive(string $key): bool
    {
        return in_array(strtolower($key), self::SENSITIVE, true);
    }

    private function ip(): ?string
    {
        if (! app()->bound('request')) {
            return null;
        }

        return request()->ip();
    }

    private function userAgent(): ?string
    {
        if (! app()->bound('request')) {
            return null;
        }

        $agent = request()->userAgent();

        return $agent ? mb_substr($agent, 0, 2000) : null;
    }
}
