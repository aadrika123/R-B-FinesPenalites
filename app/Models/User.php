<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $guarded = [];

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
            ->where('suspended', false)
            ->whereIn('user_type', ['EO', 'JSK'])
            ->orderBy('id')
            ->get();
    }



    // ======================================== User Master =================================

    /**
     * Add a new User
     */
    public function store(array $req)
    {
        return User::create($req);
    }
    /**
     * Check for Existing User
     */
    public function checkExisting($req)
    {
        $userDtl = User::where('email', $req->email)
            ->where('suspended', false)
            ->first();

        if (!$userDtl)
            $userDtl = User::where('mobile', $req->mobileNo)
                ->where('suspended', false)
                ->first();

        return $userDtl;
    }

    /**
     * Get All Users List
     */
    public function recordDetails()
    {
        $docUrl = Config::get('constants.DOC_URL');
        return User::select(
            "users.id",
            "users.user_name",
            "users.mobile",
            "users.email",
            "users.user_type",
            "users.address",
            "users.designation",
            "users.employee_code",
            "users.first_name",
            "users.middle_name",
            "users.last_name",
            "users.created_at as date",
            "wf_role_id",
            "role_name",
            DB::raw(
                "CASE 
                        WHEN profile_image IS NULL THEN ''
                            else 
                        concat('$docUrl/','FinePenalty/Users/',profile_image)
                END as profile_image,
                CASE 
                        WHEN signature IS NULL THEN ''
                            else
                        concat('$docUrl/','FinePenalty/Users/',signature)
                END as signature",
            )
        )

            ->leftjoin('wf_roleusermaps', function ($join) {
                $join->on('wf_roleusermaps.user_id', 'users.id')
                    ->where('wf_roleusermaps.is_suspended', false);
            })
            ->leftjoin('wf_roles', 'wf_roles.id', 'wf_roleusermaps.wf_role_id')
            //check if the role is suspended or not
            ->where('suspended', false)
            ->orderByDesc('id');
    }
}
