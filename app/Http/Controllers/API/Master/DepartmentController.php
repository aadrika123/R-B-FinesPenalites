<?php

namespace App\Http\Controllers\API\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\Department;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DepartmentController extends Controller
{
    private $_mDepartments;

    public function __construct()
    {
        $this->_mDepartments = new Department();
    }

    /**
     * |  Create Violation 
     */
    public function createDepartment(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'departmentName'        => 'required|string'
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $isGroupExists = $this->_mDepartments->checkExisting($req);
            if (collect($isGroupExists)->isNotEmpty())
                throw new Exception("Department Already Existing");

            $metaReqs = [
                'department_name'   => strtoupper($req->departmentName)
            ];
            $this->_mDepartments->store($metaReqs); // Store in Violations table
            return responseMsgs(true, "", $metaReqs, "100107", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "100107", "01", responseTime(), $req->getMethod(), $req->deviceId);
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
            $getData = $this->_mDepartments::findOrFail($req->departmentId);
            $isExists = $this->_mDepartments->checkExisting($req);
            if ($isExists && $isExists->where('id', '!=', $req->departmentId)->isNotEmpty())
                throw new Exception("Department Already Existing");
            $metaReqs = [
                'department_name' => strtoupper($req->departmentName),
                'updated_at' => Carbon::now()
            ];
            $getData->update($metaReqs); // Store in Violations table
            return responseMsgs(true, "", $metaReqs, "100107", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "100107", "01", responseTime(), $req->getMethod(), $req->deviceId);
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
            $getData = $this->_mDepartments->recordDetails()->where('departments.id', $req->departmentId)->first();
            if (collect($getData)->isEmpty())
                throw new Exception("Data Not Found");
            return responseMsgs(true, "", $getData, "100107", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "100107", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }
    /**
     * Get Violation List
     */
    public function getDepartmentList(Request $req)
    {
        try {
            $getData = $this->_mDepartments->recordDetails()->get();
            return responseMsgs(true, "", $getData, "100107", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "100107", "01", responseTime(), $req->getMethod(), $req->deviceId);
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
            $delete = $this->_mDepartments::findOrFail($req->departmentId);
            $delete->update($metaReqs);
            return responseMsgs(true, "", $metaReqs, "100107", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "100107", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

}
