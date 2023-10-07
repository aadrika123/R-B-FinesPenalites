<?php

namespace App\Http\Controllers\API\Master;

use App\Http\Controllers\Controller;
use App\Models\WfRole;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str; 

/**
 * =======================================================================================================
 * ===================         Created By : Umesh Kumar        ==========================================
 * ===================         Created On : 06-10-2023          ==========================================
 * =======================================================================================================
 * | Status : Open
 */

class WfRoleMasterController extends Controller
{
    private $_mWfRoles;

    public function __construct()
    {
        $this->_mWfRoles = new WfRole();
    }

    /**
     * |  Create WfRole 
     */
    public function createRole(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'roleName'        => 'required|string'
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $user = authUser($req);
            $words = explode(' ', $req->roleName); 
            $acronym = '';
            foreach ($words as $word) {
                $acronym .= strtoupper(substr($word, 0, 1)); 
            }
            $isGroupExists = $this->_mWfRoles->checkExisting($req);
            if (collect($isGroupExists)->isNotEmpty())
                throw new Exception("WfRole Already Existing");
            $metaReqs = [
                'role_name'   => $req->roleName,
                'user_type'   => $acronym,
                'created_by' => $user->id,
            ];
            // return $metaReqs; 
            $this->_mWfRoles->store($metaReqs); // Store in Violations table
            return responseMsgs(true, "Role Added Successfully", $metaReqs, "0801", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "0801", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    // Edit WfRole By Id
    public function updateRole(Request $req)
    { 
        $validator = Validator::make($req->all(), [
            'roleId'           => 'required|numeric',
            'roleName'         => 'required|string'
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $user = authUser($req);
            $words = explode(' ', $req->roleName); 
            $acronym = '';
            foreach ($words as $word) {
                $acronym .= strtoupper(substr($word, 0, 1)); 
            }
            $getData = $this->_mWfRoles::findOrFail($req->roleId);
            $isExists = $this->_mWfRoles->checkExisting($req);
            if ($isExists && $isExists->where('id', '!=', $req->roleId)->isNotEmpty())
                throw new Exception("WfRole Already Existing");
            $metaReqs = [
                'role_name'   => $req->roleName,
                'user_type'   => $acronym,
                'created_by' => $user->id,
                'updated_at' => Carbon::now()
            ];
            $getData->update($metaReqs); 
            return responseMsgs(true, "Role Updated Successfully", $metaReqs, "0802", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "0802", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * Get WfRole BY Id
     */
    public function getRoleById(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'roleId' => 'required|numeric'
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $getData = $this->_mWfRoles->recordDetails()->where('id', $req->roleId)->first();
            return $getData;
            if (collect($getData)->isEmpty())
                throw new Exception("Data Not Found");
            return responseMsgs(true, "View Role", $getData, "0803", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "0803", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }
    /**
     * Get WfRole List
     */
    public function getRoleList(Request $req)
    {
        try {
            $getData = $this->_mWfRoles->recordDetails()->get();
            return responseMsgs(true, "View All Role's Records", $getData, "0204", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "0204", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * Delete WfRole By Id
     */
    public function deleteRole(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'roleId' => 'required'
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $metaReqs =  [
                'is_suspended' => true,
            ];
            $delete = $this->_mWfRoles::findOrFail($req->roleId);
            $delete->update($metaReqs);
            return responseMsgs(true, "Role Deleted", $metaReqs, "0205", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "0205", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

}
