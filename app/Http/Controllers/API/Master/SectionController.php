<?php

namespace App\Http\Controllers\API\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\Section;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SectionController extends Controller
{
    private $_mSections;

    public function __construct()
    {
        $this->_mSections = new Section();
    }

    /**
     * |  Create Violation 
     */
    public function createSection(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "departmentId"          => 'required|numeric',
            'violationSection'      => 'required'
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $isGroupExists = $this->_mSections->checkExisting($req);
            if (collect($isGroupExists)->isNotEmpty())
                throw new Exception("Section Already Existing");

            $metaReqs = [
                'department_id' => $req->departmentId,
                'violation_section' => strtoupper($req->violationSection),
                'created_by'        => authUser()->id
            ];
            $this->_mSections->store($metaReqs); // Store in Violations table
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
            $getData = $this->_mSections::findOrFail($req->sectionId);
            $isExists = $this->_mSections->checkExisting($req);
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
    public function getSectionById(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'sectionId' => 'required|numeric'
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $getData = $this->_mSections->recordDetails($req)->where('sections.id', $req->sectionId)->first();
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
    public function getSectionList(Request $req)
    {
        try {
            $getData = $this->_mSections->recordDetails($req)->get();
            return responseMsgs(true, "View All Records", $getData, "0304", "01", responseTime(), $req->getMethod(), $req->deviceId);
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
            $delete = $this->_mSections::findOrFail($req->sectionId);
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
        $validator = Validator::make($req->all(), [
            'departmentId' => 'required'
        ]);
        if ($validator->fails())
            return validationError($validator);
        try {
            $mChallanCategories = new Section();
            $getData = $mChallanCategories->getList($req);
            return responseMsgs(true, "View Section List", $getData, "0306", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "0306", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }
}
