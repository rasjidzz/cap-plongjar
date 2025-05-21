<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class User_Role extends Model
{
    /** @use HasFactory<\Database\Factories\UserRoleFactory> */
    use HasFactory;
    protected $table = 'user_roles';
    protected $fillable = [
        'user_id',
        'role_id',
        'unitable_id',
        'unitable_type',
    ];
    public function getAllAssignedUserRole()
    {
        return self::select('user_roles.id as id_user_role', 'users.name as Nama', 'roles.name as Nama_Role')
            ->join('users', 'user_roles.user_id', '=', 'users.id')
            ->join('roles', 'user_roles.role_id', '=', 'roles.id')
            ->get();
    }
    public function unitable()
    {
        return $this->morphTo();
    }
}
