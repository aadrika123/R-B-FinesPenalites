<?php

namespace App\Http\Controllers\API\Master;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserMasterController extends Controller
{
    private $_mUsers;

    public function __construct()
    {
        $this->_mUsers = new User();
    }

    /**
     * |  Create Violation 
     */
    public function createUser(Request $req)
    {
        $validator = Validator::make($req->all(), [
            // "roleId"                  => 'required|numeric',
            // 'fullName'                => 'required|string',
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
                    'signature' => $docPath,
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
            return responseMsgs(true, "Records Added Successfully", $metaReqs, "0301", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "0301", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    // Edit records
    public function updateSection(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'sectionId'             => 'required|numeric',
            'departmentId'          => 'required|numeric',
            'violationSection'        => 'required|string'
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $getData = $this->_mUsers::findOrFail($req->sectionId);
            $isExists = $this->_mUsers->checkExisting($req);
            if ($isExists && $isExists->where('id', '!=', $req->sectionId)->isNotEmpty())  // pending
                throw new Exception("Section Already Existing");
            $metaReqs = [
                'department_id' => $req->departmentId,
                'violation_section'   => strtoupper($req->violationSection)
            ];
            $getData->update($metaReqs); // Store in Violations table
            return responseMsgs(true, "Records Updated Successfully", $metaReqs, "0302", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "0302", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * Get Violation BY Id
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
            return responseMsgs(true, "View Records", $getData, "0303", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "0303", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }
    /**
     * Get Violation List
     */
    public function getUserList(Request $req)
    {
        try {
            $perPage = $req->perPage ?? 10;
            $getData = $this->_mUsers->recordDetails($req)->paginate($perPage);
            return responseMsgs(true, "View All User's Record", $getData, "0304", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "0304", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * Delete Violation By Id
     */
    public function deleteSection(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'sectionId' => 'required'
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $metaReqs =  [
                'status' => 0
            ];
            $delete = $this->_mUsers::findOrFail($req->sectionId);
            $delete->update($metaReqs);
            return responseMsgs(true, "Deleted Successfully", $metaReqs, "0305", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "0305", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Get Section List By Department Id
     */
    public function getSectionListById(Request $req)
    {
        try {
            $mChallanCategories = new User();
            $getData = $mChallanCategories->getList($req);
            return responseMsgs(true, "View Section List", $getData, "0306", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "0306", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }
}
