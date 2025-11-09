<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\ApiToken;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Handle user login and issue an API token (stored in DB).
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password ?? '')) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Remove old tokens for this user (optional cleanup)
        ApiToken::where('user_id', $user->id)->delete();

        // Generate and store new token (24 hours by default)
        $tokenRecord = ApiToken::generateForUser($user->id, 24);

        return response()->json([
            'token'   => $tokenRecord->token,
            'user_id' => $user->id,
            'expires' => $tokenRecord->expires_at->toDateTimeString(),
        ]);
    }

    /**
     * Handle user logout and invalidate the token.
     */
    public function logout(Request $request)
    {
        $authHeader = $request->header('Authorization', '');

        if (str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7);
            ApiToken::invalidate($token);
        }

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Return authenticated user info.
     */
    public function me(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return response()->json($user);
    }
}
