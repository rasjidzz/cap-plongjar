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
            'email' => 'required|string|email|max:255|unique:users,email',
            'nip' => 'required|string|max:255|unique:users,nip', // Aturan untuk NIP
            'password' => 'required|string|min:6', // Anda mungkin ingin menambahkan konfirmasi password di sini: 'password' => 'required|string|min:6|confirmed'
        ]);
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'nip' => $validated['nip'], // Menyimpan NIP
            'password' => bcrypt($validated['password']),
        ]);

        return response()->json([
            'message' => 'Registered Successfully',
            'status_code' => 201
        ], 201);
    }
    // public function login(Request $request)
    // {
    //     $validated = $request->validate([
    //         'email' => 'required|string|email',
    //         'password' => 'required|string|min:6',
    //     ]);
    //     $user = User::where('email', $validated['email'])->first();

    //     if (!$user || !Hash::check($request->password, $user->password)) {
    //         return response()->json([
    //             'status' => 'Login Failed',
    //             'message' => 'The provided credentials are incorrect',
    //             'data' => $user
    //         ], 401);
    //     }

    //     $token = $user->createToken($request->email);

    //     return response()->json([
    //         'message' => 'Login Success',
    //         'token' => $token,
    //         'data' => $user
    //     ], 201);
    // }
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
                'message' => 'The provided credentials are incorrect'
            ], 401);
        }

        // Generate token
        // $token = $user->createToken($request->email)->plainTextToken;
        $token = $user->createToken($request->email);

        // Ambil semua role dengan informasi terkait
        $roles = User_Role::with('role', 'roleable')
            ->where('user_id', $user->id)
            ->get()
            ->map(function ($userRole) {
                return [
                    'role_id' => $userRole->role_id,
                    'role_name' => $userRole->role->name,
                    'roleable_type' => $userRole->roleable_type,
                    'roleable_id' => $userRole->roleable_id,
                    'roleable_name' => $userRole->roleable?->nama ?? null,
                ];
            });

        return response()->json([
            'status' => 'success',
            'message' => 'Login success',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'roles' => $roles
        ], 200);
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
}
