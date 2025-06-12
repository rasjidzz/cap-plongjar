<?php

namespace App\Http\Controllers;

use App\Models\KelompokKeahlian;
use App\Models\ProgramStudi;
use App\Models\Role;
use App\Models\User;
use App\Models\User_Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    protected $user_role_model;
    protected $user_model;
    public function __construct(User_Role $userRole, User $user)
    {
        $this->user_role_model = $userRole;
        $this->user_model = $user;
    }
    // public function getAllUser()
    // {
    //     return User::all();
    // }
    public function getAllUser(Request $request)
    {
        // Ambil parameter dari query string URL
        $searchNama = $request->query('nama', '');
        $searchNip = $request->query('nip', '');
        $perPage = $request->query('per_page', 9); // Default 15 item per halaman

        // Mulai membangun query dengan Eloquent
        $query = User::query();

        // Terapkan kondisi pencarian untuk nama jika parameter 'nama' ada
        $query->when($searchNama, function ($q) use ($searchNama) {
            return $q->where('name', 'like', "%{$searchNama}%");
        });

        // Terapkan kondisi pencarian untuk NIP jika parameter 'nip' ada
        $query->when($searchNip, function ($q) use ($searchNip) {
            return $q->where('nip', 'like', "%{$searchNip}%");
        });

        // Urutkan hasil (opsional) dan lakukan paginasi
        $users = $query->orderBy('name', 'asc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar pengguna berhasil dimuat.',
            'data' => $users
        ]);
    }
    public function getAllRoles()
    {
        return Role::all();
    }
    public function getAllUserByRole($id_role, Request $request)
    {
        // $userData = $this->user_model->getAllUserByRoleId($id_role);

        // return response()->json([
        //     'message' => 'All User Data by Role Fetched Successfully',
        //     'data' => $userData
        // ], 201);

        $roleExists = Role::find($id_role);
        if (!$roleExists) {
            return response()->json([
                'success' => false,
                'message' => 'Role tidak ditemukan.',
            ], 404);
        }

        // Ambil parameter pencarian dan paginasi dari query string URL
        $searchNama = $request->query('nama', '');
        $searchNip = $request->query('nip', '');
        $perPage = $request->query('per_page', 9);

        // Mulai query dengan memfilter user yang memiliki role tertentu
        $query = User::whereHas('roles', function ($q) use ($id_role) {
            $q->where('roles.id', $id_role); // Lebih spesifik dengan nama tabel 'roles.id'
        });

        // Terapkan kondisi pencarian untuk nama jika ada
        $query->when($searchNama, function ($q) use ($searchNama) {
            return $q->where('name', 'like', "%{$searchNama}%");
        });

        // Terapkan kondisi pencarian untuk NIP jika ada
        $query->when($searchNip, function ($q) use ($searchNip) {
            return $q->where('nip', 'like', "%{$searchNip}%");
        });

        // Urutkan hasil (opsional) dan lakukan paginasi
        $users = $query->orderBy('name', 'asc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar pengguna untuk role "' . $roleExists->name . '" berhasil dimuat.',
            'data' => $users
        ]);
    }
    // public function getAllAssignedUserRole()
    // {
    //     $data = $this->user_role_model->getAllAssignedUserRole();
    //     return $data;
    // }
    public function getAllAssignedUserRole()
    {
        $data = $this->user_role_model
            ->with(['user', 'role', 'roleable'])
            ->get()
            ->map(function ($item) {
                return [
                    'id_user_role' => $item->id,
                    'nama_user' => $item->user->name,
                    'nama_role' => $item->role->name,
                    'roleable_nama' => $item->roleable ? $item->roleable->nama : null,
                    'roleable_type' => class_basename($item->roleable_type),
                ];
            });

        return response()->json($data);
    }

    public function assignRole(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'role_id' => 'required|integer',
            // 'roleable_id' => 'required|integer',
            // 'roleable_type' => 'required|string'
            'roleable_id' => 'nullable|integer',
            'roleable_type' => 'nullable|string'
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
            ->where('roleable_id', $request->roleable_id)
            ->where('roleable_type', $request->roleable_type)
            ->exists();

        if ($alreadyAssigned) {
            return response()->json([
                'message' => 'User already has this role assigned'
            ], 409);
        }

        User_Role::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'roleable_id' => $request->roleable_id,
            'roleable_type' => $request->roleable_type
        ]);

        return response()->json([
            'message' => 'User Assigned to Role Successfully'
        ]);
    }
    public function assignScopedRole(Request $request)
    {
        $validatedData = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'role_id' => ['required', 'integer', Rule::in([2, 3])],
            'roleable_id' => 'required|integer',
        ], [
            'role_id.in' => 'Role yang dipilih tidak valid untuk jenis assignment ini. Hanya Program Studi atau Kelompok Keahlian yang diizinkan.'
        ]);

        $roleable_type = null;
        $roleable_exists = false;

        switch ($validatedData['role_id']) {
            case 2: // ID untuk role 'ProgramStudi'
                $roleable_type = ProgramStudi::class; // atau 'App\\Models\\ProgramStudi'
                $roleable_exists = ProgramStudi::where('id', $validatedData['roleable_id'])->exists();
                break;
            case 3: // ID untuk role 'KelompokKeahlian'
                $roleable_type = KelompokKeahlian::class; // atau 'App\\Models\\KelompokKeahlian'
                $roleable_exists = KelompokKeahlian::where('id', $validatedData['roleable_id'])->exists();
                break;
        }

        if (!$roleable_exists) {
            return response()->json([
                'success' => false,
                'message' => 'Data entitas (Program Studi/Kelompok Keahlian) dengan ID yang diberikan tidak ditemukan.',
                'errors' => ['roleable_id' => ['ID yang diberikan tidak valid untuk role ini.']]
            ], 422);
        }

        try {
            $user = User::find($validatedData['user_id']);
            $user->roles()->syncWithoutDetaching([
                $validatedData['role_id'] => [
                    'roleable_id' => $validatedData['roleable_id'],
                    'roleable_type' => $roleable_type
                ]
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Role berhasil di-assign ke user.',
                'data' => [
                    'user' => $user->load('roles'),
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal meng-assign role karena terjadi kesalahan pada server.',
            ], 500);
        }
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
    public function getAllUnassignedUser()
    {
        $unassignedUsers = User::whereDoesntHave('roles')->get();

        return response()->json([
            'message' => 'List of users without any role',
            'data' => $unassignedUsers
        ], 200);
    }
}
