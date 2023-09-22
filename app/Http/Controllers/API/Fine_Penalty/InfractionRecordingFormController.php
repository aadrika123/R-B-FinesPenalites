<?php

namespace App\Http\Controllers\API\Fine_Penalty;

use App\Http\Controllers\Controller;
use App\Http\Requests\InfractionRecordingFormRequest;
use App\Models\Fine_Penalty\InfractionRecordingForm;
use App\Models\WfRoleusermap;
use App\Models\WfWorkflowrolemap;
use App\Pipelines\FinePenalty\SearchByApplicationNo;
use App\Pipelines\FinePenalty\SearchByMobile;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pipeline\Pipeline;

class InfractionRecordingFormController extends Controller
{
    private $_mInfracRecForms;

    public function __construct()
    {
        DB::enableQueryLog();
        $this->_mInfracRecForms = new InfractionRecordingForm();
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
            $getData = InfractionRecordingForm::findOrFail($req->id);  // check the id is exists or not
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
            'id' => 'required|numeric'
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $show = $this->_mInfracRecForms->getDocument($req->id);  // get record by id
            if (collect($show)->isEmpty())
                throw new Exception("Data Not Found");
            $queryTime = collect(DB::getQueryLog())->sum("time");
            return responseMsgsT(true, "View Records", $show, "3.3", $queryTime, responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "3.3", responseTime(), "POST", $req->deviceId ?? "");
        }
    }

    /**
     * |  Retrieve All Records
     */
    public function retrieveAll(Request $req)
    {
        try {
            $getData = $this->_mInfracRecForms->retrieve();
            $perPage = $req->perPage ? $req->perPage : 10;
            $userList = app(Pipeline::class)
                ->send($getData)
                ->through([
                    SearchByApplicationNo::class,
                    SearchByMobile::class
                ])->thenReturn();
            $paginater = $userList->paginate($perPage);
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
            return responseMsgsT(true, "View All Records", $list, "3.4", $queryTime, responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "3.4", responseTime(), "POST", $req->deviceId ?? "");
        }
    }

    /**
     * |  Retrieve Only Active Records
     */
    public function activeAll(Request $req)
    {
        try {
            $getData = $this->_mInfracRecForms->active();
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
            return responseMsgsT(true, "View All Active Records", $list, "3.5", $queryTime, responseTime(), "POST", $req->deviceId ?? "");
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
            $perPage = $req->perPage ?? 10;

            $roleId = $mWfRoleusermap->getRoleIdByUserId($userId)->pluck('wf_role_id');
            $workflowIds = $mWfWorkflowRoleMaps->getWfByRoleId($roleId)->pluck('workflow_id');

            $list = InfractionRecordingForm::whereIn('workflow_id', $workflowIds)
                ->where('infraction_recording_forms.ulb_id', $ulbId)
                ->whereIn('infraction_recording_forms.current_role', $roleId)
                ->orderByDesc('infraction_recording_forms.id')
                ->paginate($perPage);


            // $inbox = app(Pipeline::class)
            //     ->send(
            //         $list
            //     )
            //     ->through([
            //         SearchByApplicationNo::class,
            //         SearchByName::class
            //     ])
            //     ->thenReturn()
            //     ->paginate($perPage);

            return responseMsgs(true, "", remove_null($list), "100107", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "100107", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | 
     */
    public function penaltyDetails(Request $req)
    {
        $req->validate([
            'applicationId' => 'required'
        ]);

        try {
            $details = array();
            $mMarriageActiveRegistration = new InfractionRecordingForm();
            // $mWorkflowTracks = new WorkflowTrack();
            // $mCustomDetails = new CustomDetail();
            // $mForwardBackward = new WorkflowMap();
            $details = MarriageActiveRegistration::find($req->applicationId);
            if (!$details)
                throw new Exception("Application Not Found");
            $witnessDetails = array();

            for ($i = 0; $i < 3; $i++) {
                $index = $i + 1;
                $name = "witness$index" . "_name";
                $mobile = "witness$index" . "_mobile_no";
                $address = "witness$index" . "_residential_address";
                $witnessDetails[$i]['withnessName'] = $details->$name;
                $witnessDetails[$i]['withnessMobile'] = $details->$mobile;
                $witnessDetails[$i]['withnessAddress'] = $details->$address;
            }
            if (!$details)
                throw new Exception("Application Not Found for this id");

            // Data Array
            $marriageDetails = $this->generateMarriageDetails($details);         // (Marriage Details) Trait function to get Marriage Details
            $marriageElement = [
                'headerTitle' => "Marriage Details",
                "data" => $marriageDetails
            ];

            $brideDetails = $this->generateBrideDetails($details);   // (Property Details) Trait function to get Property Details
            $brideElement = [
                'headerTitle' => "Bride Details",
                'data' => $brideDetails
            ];

            $groomDetails = $this->generateGroomDetails($details);   // (Property Details) Trait function to get Property Details
            $groomElement = [
                'headerTitle' => "Groom Details",
                'data' => $groomDetails
            ];

            $groomElement = [
                'headerTitle' => "Groom Details",
                'data' => $groomDetails
            ];

            // $fullDetailsData->application_no = $details->application_no;
            $fullDetailsData['application_no'] = $details->application_no;
            $fullDetailsData['apply_date'] = $details->created_at->format('d-m-Y');
            $fullDetailsData['fullDetailsData']['dataArray'] = new Collection([$marriageElement, $brideElement, $groomElement]);

            $witnessDetails = $this->generateWitnessDetails($witnessDetails);   // (Property Details) Trait function to get Property Details

            // Table Array
            $witnessElement = [
                'headerTitle' => 'Witness Details',
                'tableHead' => ["#", "Witness Name", "Witness Mobile No", "Address"],
                'tableData' => $witnessDetails
            ];

            $fullDetailsData['fullDetailsData']['tableArray'] = new Collection([$witnessElement]);
            // Card Details
            $cardElement = $this->generateCardDtls($details);
            $fullDetailsData['fullDetailsData']['cardArray'] = $cardElement;

            // $levelComment = $mWorkflowTracks->getTracksByRefId($mRefTable, $req->applicationId);
            // $fullDetailsData['levelComment'] = $levelComment;

            // $citizenComment = $mWorkflowTracks->getCitizenTracks($mRefTable, $req->applicationId, $details->user_id);
            // $fullDetailsData['citizenComment'] = $citizenComment;

            $metaReqs['customFor'] = 'MARRIAGE';
            $metaReqs['wfRoleId'] = $details->current_role;
            $metaReqs['workflowId'] = $details->workflow_id;
            $metaReqs['lastRoleId'] = $details->last_role_id;
            $req->request->add($metaReqs);

            // $forwardBackward = $mForwardBackward->getRoleDetails($req);
            // $fullDetailsData['roleDetails'] = collect($forwardBackward)['original']['data'];

            $fullDetailsData['timelineData'] = collect($req);

            // $custom = $mCustomDetails->getCustomDetails($req);
            // $fullDetailsData['departmentalPost'] = collect($custom)['original']['data'];

            return responseMsgs(true, "Marriage Details", $fullDetailsData, "100108", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "100108", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }
}
