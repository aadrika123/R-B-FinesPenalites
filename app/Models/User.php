<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    //insert registration
    public function insertData($req)
    {
        $mObject = new User();
        $dataArr = array();
        $ip = getClientIpAddress();
        $insert = [
            $mObject->name        = $req['name'],
            $mObject->email       = $req['email'],
            $mObject->password    = Hash::make($req->password),
            //   $mObject->remember_token  = createToken('auth_token')->plainTextToken
        ];
        // print_r($insert);die;
        // $token = $mObject->createToken('auth_token')->plainTextToken;
        $mObject->save($insert);
        $dataArr['name'] = $mObject->name;
        $dataArr['email'] = $mObject->email;
        $dataArr['password'] = $mObject->password;
        // $dataArr['token'] = $token;
        return $dataArr;
    }

     /**
     * | Get User by Email
     */
    public function getUserByEmail($email)
    {
        return User::where('email', $email)
            ->first();
    }

    public function getUserById($userId)
    {
        return User::select('users.*')
            // ->join('ulb_masters', 'ulb_masters.id', 'users.ulb_id')
            ->where('users.id', $userId)
            ->first();
    }

    /**
     * | getUserRoleDtls
     */
    public function getUserRoleDtls()
    {
        return  User::select('users.*')
            // ->leftjoin('wf_roleusermaps', 'wf_roleusermaps.user_id', 'users.id')
            // ->leftjoin('wf_roles', 'wf_roles.id', 'wf_roleusermaps.wf_role_id')
            ->where('suspended', false);
            // ->where('wf_roleusermaps.is_suspended', false);
    }
    /*Read all Records by*/
    public function getList()
    {
        return User::select('*')
        ->orderBy('id')
        ->get();
    }
}
