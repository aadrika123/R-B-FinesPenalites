<?php

namespace App\Http\Controllers\API\Master;

use App\Http\Controllers\Controller;
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
     * |  Create Violation Name
     */
    // Add records 
    public function createViolation(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'violationName'       => 'required|string',
            'violationSection' => 'required|string',
            'penaltyAmount' => 'required|integer',
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $isGroupExists = $this->_mViolations->checkExisting($req);
            if (collect($isGroupExists)->isNotEmpty())
                throw new Exception("Violation Name Already Existing");
            $metaReqs = [
                'violation_name' => $req->violationName,
                'violation_section' => $req->violationSection,
                'penalty_amount' => $req->penaltyAmount,
            ];
            $vioData = $this->_mViolations->store($metaReqs);
            $data = ['Violation' => $req->violationName];
            $violationSection = [
                'violation_id' => $vioData->id,
                'violation_section' => $req->violationSection,
                'penalty_amount' => $req->penaltyAmount,
            ];
            $vioData = new Violation();
            $vioData->store($violationSection);
            $queryTime = collect(DB::getQueryLog())->sum("time");
            return responseMsgsT(true, "Records Added Successfully", $data, "M_API_9.1", $queryTime, responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "M_API_9.1", responseTime(), "POST", $req->deviceId ?? "");
        }
    }

    // Edit records
    public function updateViolation(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'id'               => 'required|numeric',
            'violationName'    => 'required|string',
            'violationSection' => 'required|string',
            'penaltyAmount'    => 'required|integer',
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $isExists = $this->_mViolations->checkExisting($req);
            if ($isExists && $isExists->where('id', '!=', $req->id)->isNotEmpty())
                throw new Exception("Violation Name Already Existing");
            $getData = $this->_mViolations::findOrFail($req->id);
            $metaReqs = [
                'violation_name' => $req->violationName ?? $getData->violation_name,
                'violation_section' => $req->violationSection,
                'penalty_amount' => $req->penaltyAmount,
                'version_no' => $getData->version_no + 1,
                'updated_at' => Carbon::now()
            ];
            $getData->update($metaReqs);
            $data = ['Violation' => $req->violationName];
            $violationSection = [
                'violation_id' => $req->id,
                'violation_section' => $req->violationSection,
                'penalty_amount' => $req->penaltyAmount,
            ];
            $vioData = Violation::where('violation_id', $req->id);
            $vioData->update($violationSection);
            $queryTime = collect(DB::getQueryLog())->sum("time");
            return responseMsgsT(true, "Records Updated Successfully", $data, "M_API_9.2", $queryTime, responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "M_API_9.2", responseTime(), "POST", $req->deviceId ?? "");
        }
    }

    //show data by id
    public function ViolationById(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'id' => 'required|numeric'
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $show = $this->_mViolations->getRecordById($req->id);
            if (collect($show)->isEmpty())
                throw new Exception("Data Not Found");
            $queryTime = collect(DB::getQueryLog())->sum("time");
            return responseMsgsT(true, "View Records", $show, "M_API_9.3", $queryTime, responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "M_API_9.3", responseTime(), "POST", $req->deviceId ?? "");
        }
    }
    //View All
    public function getViolation(Request $req)
    {
        try {
            $getData = $this->_mViolations->retrieve();
            $queryTime = collect(DB::getQueryLog())->sum("time");
            return responseMsgsT(true, "View All Records", $getData, "M_API_9.4", $queryTime, responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "M_API_9.4", responseTime(), "POST", $req->deviceId ?? "");
        }
    }

    //Activate / Deactivate
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
            $queryTime = collect(DB::getQueryLog())->sum("time");
            return responseMsgsT(true, "Deleted Successfully", $req->id, "", $queryTime, responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "", responseTime(), "POST", $req->deviceId ?? "");
        }
    }
}
