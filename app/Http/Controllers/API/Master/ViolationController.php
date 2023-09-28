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
            'department'        => 'required|string',
            'violationName'     => 'required|string',
            'violationSection'  => 'required|string',
            'penaltyAmount'     => 'required|integer',
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            // $isGroupExists = $this->_mViolations->checkExisting($req);
            // if (collect($isGroupExists)->isNotEmpty())
            //     throw new Exception("Violation Name Already Existing");

            $mDepartment = new Department();  
            $getDepartment = $mDepartment::where(DB::raw('upper(department_name)'), strtoupper($req->department))->first(); 

            if(!$getDepartment){
                $departmentData = [
                    'department_name' => $req->department,
                ];
                $department = $mDepartment->store($departmentData);
                $departmentId = $department->id;
            }

            $mSections = new Section();
            $getSection = $mSections::where(DB::raw('upper(violation_section)'), strtoupper($req->violationSection))->first(); 
            if(!$getSection){
                $sectionReqs = [
                    'violation_section' => $req->violationSection,
                    'department_id' => $departmentId ?? $getDepartment->id,
                ];
                return $sectionReqs; die; 
                $section = $mSections->store($sectionReqs);  
                $sectionId = $section->id;
            }

            $metaReqs = [
                'violation_name'  => $req->violationName,
                'section_id'      => $sectionId ?? $getSection->id,
                'department_id'   => $departmentId ?? $getDepartment->id,
                'penalty_amount'  => $req->penaltyAmount,
            ];
            $this->_mViolations->store($metaReqs); // Store in Violations table
            return responseMsgs(true, "", $metaReqs, "100107", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "100107", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    // Edit records
    public function updateViolation(Request $req)
    { 
        $validator = Validator::make($req->all(), [
            'id'                => 'required|numeric',
            'department'        => 'required|string',
            'violationName'     => 'required|string',
            'violationSection'  => 'required|string',
            'penaltyAmount'     => 'required|integer',
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $isExists = $this->_mViolations->checkExisting($req);
            // if ($isExists && $isExists->where('id', '!=', $req->id)->isNotEmpty())
            //     throw new Exception("Violation Name Already Existing");

            $getData = $this->_mViolations::findOrFail($req->id);

            $mDepartment = new Department();  
            $getDepartment = $mDepartment::where('id', $getData->department_id)->first();
            if(strtoupper($getDepartment->department_name) !== strtoupper($req->department)){
                $departmentData = [
                    'department_name' => $req->department,
                ];
                // return $departmentData; die; 
                $department = $mDepartment->store($departmentData);
                $departmentId = $department->id;
            }

            $mSections = new Section();
            $getSection = $mSections::where('id', $getData->section_id)->first();
            if(strtoupper($getSection->violation_section) !== strtoupper($req->violationSection)){
                $sectionReqs = [
                    'violation_section' => $req->violationSection,
                    'department_id' => $departmentId ?? $getDepartment->id,
                ];
                $section = $mSections->store($sectionReqs);  
                $sectionId = $section->id;
            }
            $metaReqs = [
                'violation_name' => $req->violationName,
                'section_id' => $sectionId ?? $getSection->id,
                'department_id' => $departmentId ?? $getDepartment->id,
                'penalty_amount' => $req->penaltyAmount,
                'updated_at' => Carbon::now()
            ];
            $getData->update(['status' => '0']);
            $this->_mViolations->store($metaReqs); // Store in Violations table
            return responseMsgs(true, "", $metaReqs, "100107", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "100107", "01", responseTime(), $req->getMethod(), $req->deviceId);
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
            return responseMsgs(true, "", $getData, "100107", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "100107", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }
    /**
     * Get Violation List
     */
    public function getViolation(Request $req)
    {
        try {
            $getData = $this->_mViolations->recordDetails()->get();
            return responseMsgs(true, "", $getData, "100107", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "100107", "01", responseTime(), $req->getMethod(), $req->deviceId);
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
            return responseMsgs(true, "", $metaReqs, "100107", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "100107", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }
}
