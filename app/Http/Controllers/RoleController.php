<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Models\User_Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    protected $user_role_model;
    protected $user_model;
    public function __construct(User_Role $userRole, User $user)
    {
        $this->user_role_model = $userRole;
        $this->user_model = $user;
    }
    public function getAllUser()
    {
        return User::all();
    }
    public function getAllRoles()
    {
        return Role::all();
    }
    public function getAllUserByRole($id_role)
    {
        $userData = $this->user_model->getAllUserByRoleId($id_role);

        return response()->json([
            'message' => 'All User Data by Role Fetched Successfully',
            'data' => $userData
        ], 201);
    }
    public function getAllAssignedUserRole()
    {
        $data = $this->user_role_model->getAllAssignedUserRole();
        return $data;
    }
    public function assignRole(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'role_id' => 'required|integer',
        ]);

        $user = User::find($request->user_id);
        if (!$user) {
            return response()->json([
                'message' => 'User not Found'
            ], 401);
        }

        $role = Role::find($request->role_id);
        if (!$role) {
            return response()->json([
                'message' => 'Role not Found'
            ], 401);
        }
        $alreadyAssigned = User_Role::where('user_id', $user->id)
            ->where('role_id', $role->id)
            ->exists();

        if ($alreadyAssigned) {
            return response()->json([
                'message' => 'User already has this role assigned'
            ], 409);
        }

        User_Role::create([
            'user_id' => $user->id,
            'role_id' => $role->id
        ]);

        return response()->json([
            'message' => 'User Assigned to Role Successfully'
        ]);
    }
    public function revokeRole(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'role_id' => 'required|integer',
        ]);

        $deleted = User_Role::where('user_id', $request->user_id)
            ->where('role_id', $request->role_id)
            ->delete();

        if ($deleted) {
            return response()->json([
                'message' => 'Role revoked successfully from user'
            ]);
        } else {
            return response()->json([
                'message' => 'Role not found for this user'
            ], 404);
        }
    }
}
