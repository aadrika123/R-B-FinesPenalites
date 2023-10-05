<?php

namespace App\Http\Controllers\API\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\Department;
use App\Models\Master\Section;
use App\Models\Master\Violation;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\req;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class ViolationController extends Controller
{
    private $_mViolations;

    public function __construct()
    {
        DB::enableQueryLog();
        $this->_mViolations = new Violation();
    }

    /**
     * |  Create Violation 
     */
    public function createViolation(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'departmentId'      => 'required|integer',
            'sectionId'         => 'required|integer',
            'violationName'     => 'required|string',
            'penaltyAmount'     => 'required|integer',
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $isGroupExists = $this->_mViolations->checkExisting($req);
            if (collect($isGroupExists)->isNotEmpty())
                throw new Exception("Violation Name Already Existing");
            $user = authUser($req);
            $metaReqs = [
                'violation_name'  => $req->violationName,
                'section_id'      => $req->sectionId,
                'department_id'   => $req->departmentId,
                'penalty_amount'  => $req->penaltyAmount,
                'user_id'         => $user->id,
            ];
            $this->_mViolations->store($metaReqs); // Store in Violations table
            return responseMsgs(true, "", $metaReqs, "0401", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "0401", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    // Edit records
    public function updateViolation(Request $req)
    { 
        $validator = Validator::make($req->all(), [
            'violationId'       => 'required|numeric',
            'departmentId'      => 'required|integer',
            'sectionId'         => 'required|integer',
            'violationName'     => 'required|string',
            'penaltyAmount'     => 'required|integer',
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $getData = $this->_mViolations::findOrFail($req->violationId);
            $isExists = $this->_mViolations->checkExisting($req);
            if ($isExists && $isExists->where('id', '!=', $req->violationId)->isNotEmpty())
                throw new Exception("Violation Name Already Existing");
            $metaReqs = [
                'violation_name'   => $req->violationName,
                'section_id'       => $req->sectionId ?? $getData->id,
                'department_id'    => $req->departmentId ?? $getData->id,
                'penalty_amount'   => $req->penaltyAmount,
                'updated_at'       => Carbon::now()
            ];
            $getData->update($metaReqs); // Store in Violations table
            return responseMsgs(true, "", $metaReqs, "0402", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "0402", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * Get Violation BY Id
     */
    public function ViolationById(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'id' => 'required|numeric'
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $getData = $this->_mViolations->recordDetails()->where('violations.id', $req->id)->first();
            if (collect($getData)->isEmpty())
                throw new Exception("Data Not Found");
            return responseMsgs(true, "", $getData, "0403", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "0403", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }
    /**
     * Get Violation List
     */
    public function getViolation(Request $req)
    {
        try {
            $getData = $this->_mViolations->recordDetails()->get();
            return responseMsgs(true, "", $getData, "0404", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "0404", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * Delete Violation By Id
     */
    public function deleteViolation(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'id' => 'required'
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $metaReqs =  [
                'status' => 0
            ];
            $delete = $this->_mViolations::findOrFail($req->id);
            $delete->update($metaReqs);
            return responseMsgs(true, "", $metaReqs, "0405", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "0405", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }


    /**
     * | Get Violation List By Department Id & Section Id
     */
    public function getViolationListBySectionId(Request $req)
    {
        try {
            $mChallanCategories = new Violation();
            $getData = $mChallanCategories->getList($req);
            return responseMsgs(true, "", $getData, "0406", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "0406", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    public function onSpotViolation(Request $req){

        $validator = Validator::make($req->all(), [
            
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $isGroupExists = $this->_mViolations->checkExisting($req);
            if (collect($isGroupExists)->isNotEmpty())
                throw new Exception("Violation Name Already Existing");
            $user = authUser($req);
            $metaReqs = [
                'violation_name'  => $req->violationName,
                'section_id'      => $req->sectionId,
                'department_id'   => $req->departmentId,
                'penalty_amount'  => $req->penaltyAmount,
                'user_id'         => $user->id,
            ];
            $this->_mViolations->store($metaReqs); // Store in Violations table
            return responseMsgs(true, "", $metaReqs, "0401", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "0401", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }

    }
    
}
