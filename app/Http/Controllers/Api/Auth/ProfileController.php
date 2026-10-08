<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\UserResource;
use App\Services\ActivityLogService;
use App\Services\StaffService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class ProfileController extends ApiController
{
    public function show(Request $request): JsonResponse
    {
        $request->user()->load('roleRecord.permissions', 'staff.category');

        return $this->success(new UserResource($request->user()), 'Profile loaded');
    }

    public function update(Request $request, StaffService $staff, ActivityLogService $activity): JsonResponse
    {
        if ($request->filled('current_password') && ($request->filled('password') || $request->filled('new_password'))) {
            $this->changePassword($request, $activity, false);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'emergency_contact' => ['nullable', 'string', 'max:255'],
            'emergency_contact_number' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date'],
            'profile_photo' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $staff->updateOwnProfile($request->user(), $data);

        return $this->success(new UserResource($user), 'Profile updated');
    }

    public function password(Request $request, ActivityLogService $activity): JsonResponse
    {
        $this->changePassword($request, $activity, true);

        return $this->success(null, 'Password updated');
    }

    private function changePassword(Request $request, ActivityLogService $activity, bool $requireConfirmation): void
    {
        $this->aliasPassword($request);

        $passwordRules = ['required', Password::min(8)];

        if ($requireConfirmation || $request->filled('password_confirmation')) {
            $passwordRules[] = 'confirmed';
        }

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => $passwordRules,
        ]);

        $user = $request->user();

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->password = $data['password'];
        $user->save();
        $activity->log('UPDATE', 'auth', 'Password changed', $user, null, null, 'success', $user);
    }

    private function aliasPassword(Request $request): void
    {
        $merge = [];

        if (! $request->filled('password') && $request->filled('new_password')) {
            $merge['password'] = $request->input('new_password');
        }

        if (! $request->filled('password_confirmation')) {
            $confirmation = $request->input('new_password_confirmation', $request->input('confirm_password'));

            if ($confirmation !== null) {
                $merge['password_confirmation'] = $confirmation;
            }
        }

        if ($merge !== []) {
            $request->merge($merge);
        }
    }
}
