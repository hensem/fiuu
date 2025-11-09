<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Traits\LogsChanges;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    use LogsChanges;

    /**
     * List all users with creator email instead of ID.
     */
    public function index()
    {
        $users = DB::table('users')
            ->leftJoin('users as creator', 'users.created_by', '=', 'creator.id')
            ->select(
                'users.id',
                'users.email',
                DB::raw("'user' as role_name"), // static text for single user type
                'users.created_at',
                'creator.email as created_by_email' // <-- THIS is the key field
            )
            ->orderBy('users.id', 'asc')
            ->get();

        return response()->json(['data' => $users]);
    }

    /**
     * Create a new user.
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ]);

        $user = new User();
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->role = 1;
        $user->created_at = Carbon::now();
        $user->created_by = Auth::id();
        $user->updated_by = Auth::id();
        $user->save();

        $this->logModelDiff('UserController', 'store', null, $user);

        return response()->json($user, 201);
    }
}
