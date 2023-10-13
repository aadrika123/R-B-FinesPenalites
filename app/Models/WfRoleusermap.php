<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WfRoleusermap extends Model
{
    use HasFactory;
    protected $guarded = [];

    /**
     * |
     */
    public function store($req)
    {
        return WfRoleusermap::create($req);
    }

    /**
     * |
     */
    public function getRoleIdByUserId($userId)
    {
        $roles = WfRoleusermap::select('id', 'wf_role_id', 'user_id')
            ->where('user_id', $userId)
            ->where('is_suspended', false)
            ->get();
        return $roles;
    }
}
