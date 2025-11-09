<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ApiToken;
use Carbon\Carbon;

class ApiAuth
{
    /**
     * Handle an incoming request using DB-backed API tokens.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next)
    {
        $authHeader = $request->header('Authorization');

        // --- Validate header presence and format ---
        if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json([
                'message' => 'Unauthorized: Missing or invalid token header'
            ], 401);
        }

        $token = trim(substr($authHeader, 7));

        // --- Lookup token in DB ---
        $record = ApiToken::where('token', $token)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$record) {
            return response()->json([
                'message' => 'Unauthorized: Invalid or expired token'
            ], 401);
        }

        // --- Ensure user still exists ---
        $user = $record->user;
        if (!$user) {
            // Token record orphaned — remove it to keep DB clean
            $record->delete();

            return response()->json([
                'message' => 'Unauthorized: Associated user not found'
            ], 401);
        }

        // --- Authenticate user for this request only ---
        Auth::setUser($user);

        // --- Optional: sliding expiration (extend life by 24 hours on activity) ---
        try {
            $record->update(['expires_at' => Carbon::now()->addHours(24)]);
        } catch (\Throwable $e) {
            // Fail silently — do not break the request if DB update fails
        }

        // Proceed to next layer (controller)
        return $next($request);
    }
}
