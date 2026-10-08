<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Api\ApiController;
use App\Models\BusinessSetting;
use App\Models\Permission;
use App\Models\Role;
use App\Services\ActivityLogService;
use App\Support\Permissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BusinessSettingController extends ApiController
{
    public function show(): JsonResponse
    {
        return $this->success($this->payload($this->settings()), 'Settings loaded');
    }

    public function update(Request $request, ActivityLogService $activity): JsonResponse
    {
        $this->aliasBusiness($request);

        $data = $request->validate([
            'business_name' => ['sometimes', 'string', 'max:255'],
            'logo' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'gst_number' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'currency' => ['sometimes', 'string', 'max:10'],
            'currency_symbol' => ['sometimes', 'string', 'max:10'],
            'timezone' => ['sometimes', 'timezone'],
        ]);

        $settings = DB::transaction(function () use ($data, $request, $activity) {
            $settings = BusinessSetting::query()->firstOrFail();
            $before = $settings->only(array_keys($data));
            $settings->fill($data);
            $settings->save();
            $activity->log('UPDATE', 'settings', 'Business settings updated', $settings, $before, $settings->only(array_keys($data)), 'success', $request->user());

            return $settings;
        });

        return $this->success($this->payload($settings), 'Settings updated');
    }

    public function updateBusiness(Request $request, ActivityLogService $activity): JsonResponse
    {
        return $this->update($request, $activity);
    }

    public function updatePermissions(Request $request, ActivityLogService $activity): JsonResponse
    {
        $input = $request->input('permissions', $request->only(['owner', 'manager', 'staff']));

        if (! is_array($input)) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => ['permissions' => ['A permission matrix is required.']],
            ], 422);
        }

        DB::transaction(function () use ($input, $request, $activity) {
            $before = $this->permissionMatrix();
            $owner = Role::query()->where('slug', 'owner')->firstOrFail();
            $owner->permissions()->sync(Permission::query()->pluck('id')->all());

            foreach (['manager', 'staff'] as $slug) {
                if (! isset($input[$slug]) || ! is_array($input[$slug])) {
                    continue;
                }

                $universe = $slug === 'staff'
                    ? Permissions::STAFF
                    : Permissions::ALL;

                $enabled = [];

                foreach ($input[$slug] as $name => $allowed) {
                    if (! $allowed) {
                        continue;
                    }

                    $canonical = Permissions::canonical((string) $name);

                    if ($canonical && in_array($canonical, $universe, true)) {
                        $enabled[] = $canonical;
                    }
                }

                $role = Role::query()->where('slug', $slug)->firstOrFail();
                $role->permissions()->sync(
                    Permission::query()->whereIn('name', array_unique($enabled))->pluck('id')->all()
                );
            }

            $activity->log(
                'PERMISSION_CHANGED',
                'settings',
                'Role permissions updated',
                $owner,
                $before,
                $this->permissionMatrix(),
                'success',
                $request->user(),
            );
        });

        return $this->success($this->payload($this->settings()), 'Permissions updated');
    }

    private function aliasBusiness(Request $request): void
    {
        $merge = [];

        if ($request->exists('garage_name') && ! $request->exists('business_name')) {
            $merge['business_name'] = $request->input('garage_name');
        }

        if ($request->exists('logo_url') && ! $request->exists('logo')) {
            $merge['logo'] = $request->input('logo_url');
        }

        if ($merge !== []) {
            $request->merge($merge);
        }
    }

    private function settings(): BusinessSetting
    {
        return BusinessSetting::query()->firstOrFail();
    }

    private function payload(BusinessSetting $settings): array
    {
        return [
            'garage_name' => $settings->business_name,
            'business_name' => $settings->business_name,
            'address' => $settings->address,
            'phone' => $settings->phone,
            'email' => $settings->email,
            'gst_number' => $settings->gst_number,
            'logo_url' => $settings->logo,
            'logo' => $settings->logo,
            'currency' => $settings->currency,
            'currency_symbol' => $settings->currency_symbol,
            'timezone' => $settings->timezone,
            'permissions' => $this->permissionMatrix(),
        ];
    }

    /**
     * @return array<string, array<string, bool>>
     */
    private function permissionMatrix(): array
    {
        $matrix = [];

        foreach (['owner', 'manager', 'staff'] as $slug) {
            $names = $slug === 'owner'
                ? Permissions::ALL
                : Role::query()->where('slug', $slug)->first()?->permissions()->pluck('name')->all() ?? [];

            $matrix[$slug] = Permissions::matrix($names);
        }

        return $matrix;
    }
}
