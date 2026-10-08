<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\SecurityPinService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends ApiController
{
    public function login(LoginRequest $request, ActivityLogService $activity): JsonResponse
    {
        $user = User::query()->where('email', $request->string('email')->toString())->first();
        $password = $request->string('password')->toString();

        if (! $user || ! Hash::check($password, $user->password)) {
            $activity->log('LOGIN_FAILED', 'auth', 'Failed login attempt', $user, null, [
                'email' => $request->input('email'),
            ], 'failed', $user);

            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        if (! $user->isActive()) {
            $activity->log('LOGIN_FAILED', 'auth', 'Inactive account login attempt', $user, null, [
                'email' => $user->email,
            ], 'failed', $user);

            return response()->json([
                'success' => false,
                'message' => 'Forbidden',
            ], 403);
        }

        $user->forceFill(['last_login_at' => now()])->save();
        $token = $user->createToken('api')->plainTextToken;
        $activity->log('LOGIN', 'auth', 'Login successful', $user, null, ['email' => $user->email], 'success', $user);
        $user->load('roleRecord.permissions', 'staff');

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'token' => $token,
            'user' => (new UserResource($user))->resolve($request),
        ]);
    }

    public function logout(Request $request, ActivityLogService $activity, SecurityPinService $pins): JsonResponse
    {
        $user = $request->user();
        $pins->forget($user);
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        $activity->log('LOGOUT', 'auth', 'Logout successful', $user, null, null, 'success', $user);

        return $this->success(null, 'Logout successful');
    }

    public function me(Request $request): JsonResponse
    {
        $request->user()->load('roleRecord.permissions', 'staff');

        return $this->success(new UserResource($request->user()), 'Authenticated user');
    }
}
