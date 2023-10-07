<?php

namespace App\Http\Controllers\API\Master;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * =======================================================================================================
 * ===================         Created By : Umesh Kumar        ==========================================
 * ===================         Created On : 06-10-2023          ==========================================
 * =======================================================================================================
 * | Status : Open
 */
class UserMasterController extends Controller
{
    private $_mUsers;

    public function __construct()
    {
        $this->_mUsers = new User();
    }

    /**
     * |  Add User 
     */
    public function createUser(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'firstName'               => 'required|string',
            'middleName'              => 'required|string',
            'lastName'                => 'required|string',
            'designation'             => 'required|string',
            'mobile'                  => 'required|numeric|digits:10',
            'address'                 => 'required|string',
            'employeeCode'            => 'required|string',
            'signature'               => 'nullable|file',
            'email'                   => 'required|email',
            // 'password'                => 'required|string', 
            // 'confirmPassword'         => 'required|same:password',
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $user = authUser($req);
            $metaReqs = [];
            $isGroupExists = $this->_mUsers->checkExisting($req);
            if (collect($isGroupExists)->isNotEmpty())
                throw new Exception("User Already Existing");
            if ($req->file('signature')) {
                $file = $req->file('signature');
                $docPath = $file->move(public_path('FinePenalty/Users'), $file->getClientOriginalName());
                $file_name = 'FinePenalty/Users/' . $file->getClientOriginalName();
                $metaReqs = array_merge($metaReqs, [
                    'signature' => $file_name,
                ]);
            }
            $metaReqs = array_merge($metaReqs, [
                'first_name'     => $req->firstName,
                'middle_name'    => $req->middleName,
                'last_name'      => $req->lastName,
                'user_name'      => $req->firstName . ' ' . $req->middleName . ' ' . $req->lastName,
                'mobile'         => $req->mobile,
                'email'          => $req->email,
                'ulb_id'         => $user->id,
                'address'        => $req->address,
                'designation'    => $req->designation,
                'employee_code'  => $req->employeeCode,
            ]);
            // return $metaReqs; die; 
            $this->_mUsers->store($metaReqs);
            return responseMsgs(true, "Records Added Successfully", $metaReqs, "0901", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "0901", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * Update User
     */
    public function updateUser(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'userId'                  => 'required|numeric',
            'firstName'               => 'required|string',
            'middleName'              => 'required|string',
            'lastName'                => 'required|string',
            'designation'             => 'required|string',
            'mobile'                  => 'required|numeric|digits:10',
            'address'                 => 'required|string',
            'employeeCode'            => 'required|string',
            'signature'               => 'nullable|file',
            'email'                   => 'required|email',
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $user = authUser($req);
            $getUser = $this->_mUsers::findOrFail($req->userId);  
            $isExists = $this->_mUsers->checkExisting($req); 
            if ($isExists && $isExists->where('id', '!=', $req->userId)->isNotEmpty())
                throw new Exception("User Already Existing");
            $metaReqs = [
                'first_name'     => $req->firstName,
                'middle_name'    => $req->middleName,
                'last_name'      => $req->lastName,
                'user_name'      => $req->firstName . ' ' . $req->middleName . ' ' . $req->lastName,
                'mobile'         => $req->mobile,
                'email'          => $req->email,
                'ulb_id'         => $user->id,
                'address'        => $req->address,
                'designation'    => $req->designation,
                'employee_code'  => $req->employeeCode,
            ];
            $getUser->update($metaReqs); // Store in Violations table
            return responseMsgs(true, "User Updated Successfully", $metaReqs, "0902", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "0902", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * Get User BY Id
     */
    public function getUserById(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'userId' => 'required|numeric'
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $getData = $this->_mUsers->recordDetails($req)->where('id', $req->userId)->first();
            if (collect($getData)->isEmpty())
                throw new Exception("Data Not Found");
            return responseMsgs(true, "View User", $getData, "0903", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "0903", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }
    /**
     * Get User's List
     */
    public function getUserList(Request $req)
    {
        try {
            $perPage = $req->perPage ?? 10;
            $getData = $this->_mUsers->recordDetails($req)->paginate($perPage);
            return responseMsgs(true, "View All User's Record", $getData, "0904", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "0904", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * Delete User By Id
     */
    public function deleteUser(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'userId' => 'required'
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $metaReqs =  [
                'suspended' => true
            ];
            $delete = $this->_mUsers::findOrFail($req->userId);
            $delete->update($metaReqs);
            return responseMsgs(true, "User Deleted", $metaReqs, "0905", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "0905", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }
}
