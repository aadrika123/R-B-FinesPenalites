<?php

namespace App\Http\Controllers\API\Master;

use App\Http\Controllers\Controller;
use App\Models\WfRole;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
            $isGroupExists = $this->_mWfRoles->checkExisting($req);
            return $isGroupExists; die; 
            if (collect($isGroupExists)->isNotEmpty())
                throw new Exception("WfRole Already Existing");
            $metaReqs = [
                'role_name'   => strtoupper($req->roleName)
            ];
            $this->_mWfRoles->store($metaReqs); // Store in Violations table
            return responseMsgs(true, "Records Added Successfully", $metaReqs, "0201", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "0201", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    // Edit records
    public function updateDepartment(Request $req)
    { 
        $validator = Validator::make($req->all(), [
            'departmentId'                => 'required|numeric',
            'departmentName'        => 'required|string'
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $getData = $this->_mWfRoles::findOrFail($req->departmentId);
            $isExists = $this->_mWfRoles->checkExisting($req);
            if ($isExists && $isExists->where('id', '!=', $req->departmentId)->isNotEmpty())
                throw new Exception("WfRole Already Existing");
            $metaReqs = [
                'department_name' => strtoupper($req->departmentName),
                'updated_at' => Carbon::now()
            ];
            $getData->update($metaReqs); // Store in Violations table
            return responseMsgs(true, "Records Updated Successfully", $metaReqs, "0202", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "0202", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * Get Violation BY Id
     */
    public function getDepartmentById(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'departmentId' => 'required|numeric'
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $getData = $this->_mWfRoles->recordDetails()->where('departments.id', $req->departmentId)->first();
            if (collect($getData)->isEmpty())
                throw new Exception("Data Not Found");
            return responseMsgs(true, "View Records", $getData, "0203", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "0203", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }
    /**
     * Get Violation List
     */
    public function getDepartmentList(Request $req)
    {
        try {
            $getData = $this->_mWfRoles->recordDetails()->get();
            return responseMsgs(true, "View All Records", $getData, "0204", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "0204", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * Delete Violation By Id
     */
    public function deleteDepartment(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'departmentId' => 'required'
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $metaReqs =  [
                'status' => 0
            ];
            $delete = $this->_mWfRoles::findOrFail($req->departmentId);
            $delete->update($metaReqs);
            return responseMsgs(true, "Deleted Successfully", $metaReqs, "0205", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "0205", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

}
