<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Models\User_Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
        ]);

        return response()->json([
            'message' => 'Registered Successfully',
            'status_code' => 201
        ], 201);
    }
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string|min:6',
        ]);
        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'Login Failed',
                'message' => 'The provided credentials are incorrect',
                'data' => $user
            ], 401);
        }

        $token = $user->createToken($request->email);

        return response()->json([
            'message' => 'Login Success',
            'token' => $token,
            'data' => $user
        ], 201);
    }
    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();
        if ($token) {
            $token->delete();
        }

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    // public function assignRole(Request $request)
    // {
    //     $request->validate([
    //         'user_id' => 'required|integer',
    //         'role_id' => 'required|integer',
    //     ]);

    //     $user = User::find($request->user_id);
    //     if (!$user) {
    //         return response()->json([
    //             'message' => 'User not Found'
    //         ], 401);
    //     }

    //     $role = Role::find($request->role_id);
    //     if (!$role) {
    //         return response()->json([
    //             'message' => 'Role not Found'
    //         ], 401);
    //     }
    //     $alreadyAssigned = User_Role::where('user_id', $user->id)
    //         ->where('role_id', $role->id)
    //         ->exists();

    //     if ($alreadyAssigned) {
    //         return response()->json([
    //             'message' => 'User already has this role assigned'
    //         ], 409);
    //     }

    //     User_Role::create([
    //         'user_id' => $user->id,
    //         'role_id' => $role->id
    //     ]);

    //     return response()->json([
    //         'message' => 'User Assigned to Role Successfully',
    //     ]);
    // }
}
