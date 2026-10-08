<?php

namespace App\Http\Controllers\Api\Security;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\ChangePinRequest;
use App\Http\Requests\VerifyPinRequest;
use App\Services\SecurityPinService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SecurityPinController extends ApiController
{
    public function verify(VerifyPinRequest $request, SecurityPinService $pins): JsonResponse
    {
        $result = $pins->verify($request->user(), $request->string('pin')->toString());
        $status = match ($result['code'] ?? null) {
            'PIN_LOCKED' => 429,
            'PIN_INVALID', 'PIN_REQUIRED' => 422,
            default => 200,
        };

        if (($result['success'] ?? false) !== true && ! isset($result['code'])) {
            $status = 422;
        }

        return response()->json($result, $result['success'] ? 200 : $status);
    }

    public function status(Request $request, SecurityPinService $pins): JsonResponse
    {
        return $this->success($pins->status($request->user()), 'PIN status loaded');
    }

    public function logout(Request $request, SecurityPinService $pins): JsonResponse
    {
        $pins->forget($request->user());

        return $this->success(null, 'PIN session ended');
    }

    public function update(ChangePinRequest $request, SecurityPinService $pins): JsonResponse
    {
        $pins->change(
            $request->user(),
            $request->string('current_pin')->toString(),
            $request->string('new_pin')->toString(),
        );

        return $this->success(null, 'PIN updated');
    }
}
