<?php

namespace App\Http\Controllers\API\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\ViolationSection;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ViolationSectionController extends Controller
{
    private $_mViolationSections;

    public function __construct()
    {
        DB::enableQueryLog();
        $this->_mViolationSections = new ViolationSection();
    }

    /**
     * |  Create Violation Name
     */
    // Add records 
    public function createViolationSection(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'violationSection' => 'required|string'
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $isGroupExists = $this->_mViolationSections->checkExisting($req);
            if (collect($isGroupExists)->isNotEmpty())
                throw new Exception("Section Already Existing");
            $metaReqs = [
                'violation_section' => $req->violationSection,
            ];
            $this->_mViolationSections->store($metaReqs);
            $queryTime = collect(DB::getQueryLog())->sum("time");
            return responseMsgsT(true, "Records Added Successfully", $metaReqs, "M_API_9.1", $queryTime, responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "M_API_9.1", responseTime(), "POST", $req->deviceId ?? "");
        }
    }

    // Edit records
    public function updateViolationSection(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'id'               => 'required|numeric',
            'violationSection' => 'required|string'
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $isExists = $this->_mViolationSections->checkExisting($req);
            if ($isExists && $isExists->where('id', '!=', $req->id)->isNotEmpty())
                throw new Exception("Section Already Existing");
            $getData = $this->_mViolationSections::findOrFail($req->id);
            $metaReqs = [
                'violation_section' => $req->violationSection ?? $getData->violation_section,
                'version_no' => $getData->version_no + 1,
                'updated_at' => Carbon::now()
            ];
            $getData->update($metaReqs);
            $queryTime = collect(DB::getQueryLog())->sum("time");
            return responseMsgsT(true, "Records Updated Successfully", $metaReqs, "M_API_9.2", $queryTime, responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "M_API_9.2", responseTime(), "POST", $req->deviceId ?? "");
        }
    }

    //show data by id
    public function getSectionById(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'id' => 'required|numeric'
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $show = $this->_mViolationSections->getRecordById($req->id);
            if (collect($show)->isEmpty())
                throw new Exception("Data Not Found");
            $queryTime = collect(DB::getQueryLog())->sum("time");
            return responseMsgsT(true, "View Records", $show, "M_API_9.3", $queryTime, responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "M_API_9.3", responseTime(), "POST", $req->deviceId ?? "");
        }
    }
    //View All
    public function getSectionList(Request $req)
    {
        try {
            $getData = $this->_mViolationSections->retrieve();
            $queryTime = collect(DB::getQueryLog())->sum("time");
            return responseMsgsT(true, "View All Records", $getData, "M_API_9.4", $queryTime, responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "M_API_9.4", responseTime(), "POST", $req->deviceId ?? "");
        }
    }

    //Activate / Deactivate
    public function deleteSection(Request $req)
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
            $delete = $this->_mViolationSections::findOrFail($req->id);
            $delete->update($metaReqs);
            $queryTime = collect(DB::getQueryLog())->sum("time");
            return responseMsgsT(true, "Deleted Successfully", $req->id, "", $queryTime, responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "", responseTime(), "POST", $req->deviceId ?? "");
        }
    }
}
