<?php

namespace App\Http\Middleware;

use App\Services\SecurityPinService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePinVerified
{
    public function __construct(private SecurityPinService $pins) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $state = $user ? $this->pins->sessionState($user) : 'missing';

        if ($state === 'expired') {
            return response()->json([
                'success' => false,
                'code' => 'PIN_EXPIRED',
                'message' => 'Security PIN has expired',
            ], 403);
        }

        if ($state !== 'valid') {
            return response()->json([
                'success' => false,
                'code' => 'PIN_REQUIRED',
                'message' => 'Security PIN verification required',
            ], 403);
        }

        return $next($request);
    }
}
