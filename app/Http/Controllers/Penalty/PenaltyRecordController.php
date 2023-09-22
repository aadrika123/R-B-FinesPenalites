<?php

namespace App\Http\Controllers\Penalty;

use App\Http\Controllers\Controller;
use App\Http\Requests\InfractionRecordingFormRequest;
use App\Models\Fine_Penalty\InfractionRecordingForm;
use App\Models\PenaltyChallan;
use App\Models\PenaltyFinalRecord;
use App\Models\PenaltyRecord;
use App\Models\WfRoleusermap;
use App\Models\WfWorkflowrolemap;
use App\Pipelines\FinePenalty\SearchByApplicationNo;
use App\Pipelines\FinePenalty\SearchByMobile;
use App\Traits\Fines\FinesTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Pipeline\Pipeline;

class PenaltyRecordController extends Controller
{

    use FinesTrait;
    private $_mInfracRecForms;

    public function __construct()
    {
        DB::enableQueryLog();
        $this->_mInfracRecForms = new PenaltyRecord();
    }

    /**
     * |  Add Infraction Recording Form Data
     */
    public function store(InfractionRecordingFormRequest $req)
    {
        try {
            $isGroupExists = $this->_mInfracRecForms->checkExisting($req); // Check if record already exists or not
            if (collect($isGroupExists)->isNotEmpty())
                throw new Exception("Email Already Existing");
            $data = $this->_mInfracRecForms->store($req);  // Store record
            $queryTime = collect(DB::getQueryLog())->sum("time");
            return responseMsgsT(true, "Records Added Successfully", $data, "3.1", $queryTime, responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "3.1", responseTime(), "POST", $req->deviceId ?? "");
        }
    }

    /**
     * |  Edit Record | Update according to the given id
     */
    public function edit(InfractionRecordingFormRequest $req)
    {
        try {
            $getData = PenaltyRecord::findOrFail($req->id);  // check the id is exists or not
            $isExists = $this->_mInfracRecForms->checkExisting($req);  // check if existing
            if ($isExists && $isExists->where('id', '!=', $req->id)->isNotEmpty())
                throw new Exception("Record Already Existing");
            $data = $this->_mInfracRecForms->edit($req, $getData);  // update record
            $queryTime = collect(DB::getQueryLog())->sum("time");
            return responseMsgsT(true, "Records Updated Successfully", $data, "3.2", $queryTime, responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "3.2", responseTime(), "POST", $req->deviceId ?? "");
        }
    }

    /**
     * |  Get Record By Id
     */
    public function show(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'id' => 'required|numeric'
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $show = $this->_mInfracRecForms->getRecordById($req->id);  // get record by id
            if (collect($show)->isEmpty())
                throw new Exception("Data Not Found");
            $queryTime = collect(DB::getQueryLog())->sum("time");
            return responseMsgsT(true, "View Records", $show, "3.3", $queryTime, responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "3.3", responseTime(), "POST", $req->deviceId ?? "");
        }
    }

    public function showDocument(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'applicationId' => 'required|numeric'
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $show = $this->_mInfracRecForms->getDocument($req->applicationId);  // get record by id
            if (collect($show)->isEmpty())
                throw new Exception("Data Not Found");
            $queryTime = collect(DB::getQueryLog())->sum("time");
            return responseMsgsT(true, "View Records", $show, "3.3", $queryTime, responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "3.3", responseTime(), "POST", $req->deviceId ?? "");
        }
    }

    /**
     * |  Retrieve Only Active Records
     */
    public function activeAll(Request $req)
    {
        try {
            $perPage = $req->perPage ?? 10;
            $getData = $this->_mInfracRecForms->active();

            $userList = app(Pipeline::class)
                ->send($getData)
                ->through([
                    SearchByApplicationNo::class,
                    SearchByMobile::class
                ])->thenReturn()
                ->paginate($perPage);

            return responseMsgsT(true, "View All Active Records", $userList, "3.5", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "3.5", responseTime(), "POST", $req->deviceId ?? "");
        }
    }

    /**
     * |  Delete Records(Activate / Deactivate)
     */
    public function delete(Request $req)
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
            $delete = $this->_mInfracRecForms::findOrFail($req->id);
            $delete->update($metaReqs);
            $queryTime = collect(DB::getQueryLog())->sum("time");
            return responseMsgsT(true, "Deleted Successfully", $req->id, "3.6", $queryTime, responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "3.6", responseTime(), "POST", $req->deviceId ?? "");
        }
    }

    //view by name
    public function searchByApplicationNo(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'applicationNo' => 'required|string'
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $getData = $this->_mInfracRecForms->searchByName($req);
            $perPage = $req->perPage ? $req->perPage : 10;
            $paginater = $getData->paginate($perPage);
            // if ($paginater == "")
            //     throw new Exception("Data Not Found"); 
            $list = [
                "current_page" => $paginater->currentPage(),
                "perPage" => $perPage,
                "last_page" => $paginater->lastPage(),
                "data" => $paginater->items(),
                "total" => $paginater->total()
            ];
            $queryTime = collect(DB::getQueryLog())->sum("time");
            return responseMsgsT(true, "View Searched Records", $list, "M_API_36.7", $queryTime, responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "M_API_36.7", responseTime(), "POST", $req->deviceId ?? "");
        }
    }

    /**
     * ========================================================================================================
     * ===================         Created By : Mrinal Kumar       ============================================
     * ===================         Created On : 22-09-2023         ============================================
     * ========================================================================================================
     */


    /**
     * | Inbox List
     */
    public function inbox(Request $req)
    {
        try {
            $user = authUser($req);
            $userId = $user->id;
            $ulbId = $user->ulb_id;
            $mWfWorkflowRoleMaps = new WfWorkflowrolemap();
            $mWfRoleusermap = new WfRoleusermap();
            $mPenaltyRecord = new PenaltyRecord();
            $perPage = $req->perPage ?? 10;

            $roleId = $mWfRoleusermap->getRoleIdByUserId($userId)->pluck('wf_role_id');
            $workflowIds = $mWfWorkflowRoleMaps->getWfByRoleId($roleId)->pluck('workflow_id');

            $list = $mPenaltyRecord->active()
                ->whereIn('workflow_id', $workflowIds)
                ->where('penalty_applied_records.ulb_id', $ulbId)
                ->whereIn('penalty_applied_records.current_role', $roleId)
                ->orderByDesc('penalty_applied_records.id');

            $inbox = app(Pipeline::class)
                ->send(
                    $list
                )
                ->through([
                    SearchByApplicationNo::class,
                    SearchByMobile::class
                ])
                ->thenReturn()
                ->paginate($perPage);

            return responseMsgs(true, "", remove_null($inbox), "100107", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "100107", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Penalty Details by Id
     */
    public function penaltyDetails(Request $req)
    {
        $validator = Validator::make($req->all(), ['applicationId' => 'required|int']);
        if ($validator->fails())
            return validationError($validator);

        try {
            $details = array();
            $mPenaltyRecord = new PenaltyRecord();
            // $mWorkflowTracks = new WorkflowTrack();
            // $mCustomDetails = new CustomDetail();
            // $mForwardBackward = new WorkflowMap();
            $details = $mPenaltyRecord->getRecordById($req->applicationId);
            if (!$details)
                throw new Exception("Application Not Found");

            // Data Array
            $basicDetails = $this->generateBasicDetails($details);
            $basicElement = [
                'headerTitle' => "Basic Details",
                'data' => $basicDetails
            ];

            $penaltyDetails = $this->generatePenaltyDetails($details);         // (Penalty Details) Trait function to get Penalty Details
            $penaltyElement = [
                'headerTitle' => "Violation Details",
                "data" => $penaltyDetails
            ];

            $addressDetails = $this->generateAddressDetails($details);
            $addressElement = [
                'headerTitle' => "Address Details",
                'data' => $addressDetails
            ];

            $witnessDetails = $this->generateWitnessDetails($details);
            $witnessElement = [
                'headerTitle' => "Witness Details",
                'data' => $witnessDetails
            ];

            $fullDetailsData['application_no'] = $details->application_no;
            $fullDetailsData['apply_date'] = date('d-m-Y', strtotime($details->created_at));
            $fullDetailsData['fullDetailsData']['dataArray'] = new Collection([$basicElement, $addressElement, $penaltyElement, $witnessElement]);

            // Card Details
            $cardElement = $this->generateCardDtls($details);
            $fullDetailsData['fullDetailsData']['cardArray'] = $cardElement;

            // $levelComment = $mWorkflowTracks->getTracksByRefId($mRefTable, $req->applicationId);
            // $fullDetailsData['levelComment'] = $levelComment;

            // $citizenComment = $mWorkflowTracks->getCitizenTracks($mRefTable, $req->applicationId, $details->user_id);
            // $fullDetailsData['citizenComment'] = $citizenComment;

            $metaReqs['customFor'] = 'PENALTY';
            $metaReqs['wfRoleId'] = $details->current_role;
            $metaReqs['workflowId'] = $details->workflow_id;
            $req->request->add($metaReqs);

            // $forwardBackward = $mForwardBackward->getRoleDetails($req);
            // $fullDetailsData['roleDetails'] = collect($forwardBackward)['original']['data'];

            $fullDetailsData['timelineData'] = collect($req);

            // $custom = $mCustomDetails->getCustomDetails($req);
            // $fullDetailsData['departmentalPost'] = collect($custom)['original']['data'];

            return responseMsgs(true, "Penalty Details", $fullDetailsData, "100108", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "100108", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Approve Penalty
     */
    public function approvePenalty(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'id' => 'required|numeric'
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);

        try {
            $user = authUser($req);
            $userId = $user->id;
            $ulbId = $user->ulb_id;
            $mWfWorkflowRoleMaps = new WfWorkflowrolemap();
            $mWfRoleusermap = new WfRoleusermap();
            $mPenaltyFinalRecord = new PenaltyFinalRecord();
            $mPenaltyChallan = new PenaltyChallan();

            $penaltyRecord = PenaltyRecord::findOrFail($req->id);  // check the id is exists or not

            $finalRecordReqs = [
                'full_name'                   => $req->fullName,
                'mobile'                      => $req->mobile,
                'email'                       => $req->email,
                'holding_no'                  => $req->holdingNo,
                'street_address'              => $req->streetAddress,
                'street_address_2'            => $req->streetAddress2,
                'city'                        => $req->city,
                'region'                      => $req->region,
                'postal_code'                 => $req->postalCode,
                'violation_id'                => $req->violationId,
                'penalty_amount'              => $req->penaltyAmount,
                'previous_violation_offence'  => $req->previousViolationOffence,
                'witness'                     => $req->witness,
                'witness_name'                => $req->witnessName,
                'witness_mobile'              => $req->witnessMobile,
                'penalty_previous_id'         => $req->id,
                'version_no'                  => 0,
                'application_no'              => $penaltyRecord->application_no,
                'current_role'                => $penaltyRecord->current_role,
                'workflow_id'                 => $penaltyRecord->workflow_id,
                'ulb_id'                      => $penaltyRecord->ulb_id,
                'approved_by'                 => $userId,
            ];

            $challanReqs = [
                'challan_no'                   => 134,
                'challan_date'                 => Carbon::now(),
                'payment_date'                 => $req->paymentDate,
                'penalty_record_id'            => $penaltyRecord->id
            ];

            DB::beginTransaction();
            $data = $mPenaltyFinalRecord->store($finalRecordReqs);
            $data = $mPenaltyChallan->store($challanReqs);
            $penaltyRecord->status = 2;
            $penaltyRecord->save();
            DB::commit();

            return responseMsgs(true, "", ["challanNo" => $data->challan_no], "100107", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "100107", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }
}
