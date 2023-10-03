<?php

namespace App\Http\Controllers\Penalty;

use App\Http\Controllers\Controller;
use App\Http\Requests\InfractionRecordingFormRequest;
use App\IdGenerator\IdGeneration;
use App\Models\Master\Section;
use App\Models\Master\Violation;
use App\Models\PenaltyChallan;
use App\Models\PenaltyDocument;
use App\Models\PenaltyFinalRecord;
use App\Models\PenaltyRecord;
use App\Models\PenaltyTransaction;
use App\Models\WfRoleusermap;
use App\Models\WfWorkflowrolemap;
use App\Pipelines\FinePenalty\SearchByApplicationNo;
use App\Pipelines\FinePenalty\SearchByChallan;
use App\Pipelines\FinePenalty\SearchByMobile;
use App\Traits\Fines\FinesTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;
use Exception;
use PhpParser\Node\Stmt\Return_;

/**
 * =======================================================================================================
 * ===================         Created By : Umesh Kumar         ==========================================
 * ===================         Created On : 20-09-2023          ==========================================
 * =======================================================================================================
 * ===================         Modified By : Mrinal Kumar       ==========================================
 * ===================         Modified On : 22-09-2023         ==========================================
 * =======================================================================================================
 * | Status : Closed
 */

class PenaltyRecordController extends Controller
{

    use FinesTrait;
    private $mPenaltyRecord;

    public function __construct()
    {
        $this->mPenaltyRecord = new PenaltyRecord();
    }

    /**
     * | Penalty Application Apply
     * | API Id : 0601
     * | Query Run Time: ~305ms
     */
    public function store(InfractionRecordingFormRequest $req)
    {
        try {
            $mSection = new Section();
            $mViolation = new Violation();
            $mPenaltyDocument = new PenaltyDocument();
            $applicationIdParam = Config::get('constants.ID_GENERATION_PARAMS.APPLICATION');
            $user = authUser();
            $ulbId = $user->ulb_id;

            $violationDtl = $mViolation->violationById($req->violationId);
            if (!$violationDtl)
                throw new Exception("Provide Valid Violation Id");

            $req->penaltyAmount = $violationDtl->penalty_amount;

            if ($req->categoryTypeId == 1)
                $req->penaltyAmount = $this->checkRickshawCondition($req);              #_Check condition for E-Rickshaw

            $sectionId = $violationDtl->section_id;
            $section = $mSection->sectionById($sectionId)->violation_section;

            $idGeneration = new IdGeneration($applicationIdParam, $ulbId, $section, 0);
            $applicationNo = $idGeneration->generate();
            $metaReqs = $this->generateRequest($req, $applicationNo);
            $metaReqs['challan_type'] = "Via Verification";

            DB::beginTransaction();

            $data = $this->mPenaltyRecord->store($metaReqs);
            if ($req->file('photo')) {
                $metaReqs['documents'] = $mPenaltyDocument->storeDocument($req, $data->id, $data->application_no);
            }

            DB::commit();
            return responseMsgs(true, "Records Added Successfully", $data, "0601",  responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), [], "", "0601", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Get Record By Id
     * | API Id : 0602
     */
    public function show(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'id' => 'required|numeric'
        ]);

        if ($validator->fails())
            return validationError($validator);
        try {
            $penaltyDetails = $this->mPenaltyRecord->recordDetail()
                //query for chalan no
                ->where('penalty_applied_records.id', $req->id)
                ->first();

            if (!$penaltyDetails)
                throw new Exception("Data Not Found");
            return responseMsgs(true, "View Records", $penaltyDetails, "0602",  responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "0602", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Retrieve Only Active Records
     * | API Id : 0603
     */
    public function activeAll(Request $req)
    {
        try {
            $perPage = $req->perPage ?? 10;
            $recordData = $this->mPenaltyRecord->recordDetail()
                ->where('penalty_applied_records.status', 1);

            $penaltyDetails = app(Pipeline::class)
                ->send($recordData)
                ->through([
                    SearchByApplicationNo::class,
                    SearchByMobile::class
                ])->thenReturn()
                ->paginate($perPage);

            return responseMsgs(true, "View All Active Records", $penaltyDetails, "0603", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "0603", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Delete Record(Deactivate)
     * | API Id : 0604
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
            $delete = $this->mPenaltyRecord::findOrFail($req->id);
            $delete->update($metaReqs);

            return responseMsgs(true, "Deleted Successfully", $req->id, "0604",  responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "0604", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Search by Application No
     * | API Id : 0605
     */
    public function searchByApplicationNo(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'applicationNo' => 'required|string'
        ]);
        if ($validator->fails())
            return responseMsgs(false, $validator->errors(), []);
        try {
            $getData = $this->mPenaltyRecord->searchByAppNo($req);
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

            return responseMsgs(true, "View Searched Records", $list, "0605", responseTime(), responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "0605", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }


    /**
     * ========================================================================================================
     * ===================         Modified By : Mrinal Kumar       ===========================================
     * ===================         Modified On : 22-09-2023         ===========================================
     * ========================================================================================================
     */

    /**
     * | Get Uploaded Document
     * | API Id : 0606
     */
    public function getUploadedDocuments(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'applicationId' => 'required|numeric'
        ]);
        if ($validator->fails())
            return validationError($validator);
        try {
            $mPenaltyDocument = new PenaltyDocument();
            $applicationDtls = $this->mPenaltyRecord->find($req->applicationId);
            if (!$applicationDtls)
                throw new Exception("Application Not Found for this application Id");

            $show = $mPenaltyDocument->getDocument($req->applicationId);  // get record by id
            if (collect($show)->isEmpty())
                throw new Exception("Data Not Found");

            return responseMsgs(true, "View Records", $show, "0606", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "0606", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Inbox List
     * | API Id : 0607
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

            $list = $mPenaltyRecord->recordDetail()
                ->where('penalty_applied_records.status', 1)
                ->where('penalty_applied_records.ulb_id', $ulbId)
                ->whereIn('workflow_id', $workflowIds)
                ->whereIn('penalty_applied_records.current_role', $roleId);

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

            return responseMsgs(true, "", remove_null($inbox), "0607", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "0607", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Penalty Details by Id
     * | API Id : 0608
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
            $details = $mPenaltyRecord->recordDetail()
                ->where('penalty_applied_records.status', 1)
                ->where('penalty_applied_records.id', $req->applicationId)
                ->first();

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
            $fullDetailsData['payment_status'] = false;
            $fullDetailsData['challan_status'] = false;
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

            return responseMsgs(true, "Penalty Details", $fullDetailsData, "0608", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "0608", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Approve Penalty
     * | API Id : 0609
     */
    public function approvePenalty(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'id' => 'required|numeric'
        ]);
        if ($validator->fails())
            return validationError($validator);

        try {
            $user = authUser($req);
            $userId = $user->id;
            $ulbId = $user->ulb_id;
            $mViolation = new Violation();
            $mPenaltyRecord = new PenaltyRecord();
            $mPenaltyChallan = new PenaltyChallan();
            $mPenaltyFinalRecord = new PenaltyFinalRecord();
            $challanIdParam = Config::get('constants.ID_GENERATION_PARAMS.CHALLAN');

            $penaltyRecord = $mPenaltyRecord->recordDetail()
                ->where('penalty_applied_records.status', 1)
                ->where('penalty_applied_records.id', $req->id)
                ->first();

            if (!$penaltyRecord)
                throw new Exception("Record Not Found");

            $violationDtl = $mViolation->violationById($req->violationId);
            if (!$violationDtl)
                throw new Exception("Provide Valid Violation Id");
            $penaltyAmount = $violationDtl->penalty_amount;

            $finalRecordReqs = [
                'full_name'                   => $req->fullName,
                'mobile'                      => $req->mobile,
                'email'                       => $req->email,
                'holding_no'                  => $req->holdingNo,
                'street_address'              => $req->streetAddress,
                'city'                        => $req->city,
                'region'                      => $req->region,
                'postal_code'                 => $req->postalCode,
                'violation_id'                => $req->violationId,
                'amount'                      => $penaltyAmount,
                'previous_violation_offence'  => $req->previousViolationOffence,
                'witness'                     => $req->witness,
                'witness_name'                => $req->witnessName,
                'witness_mobile'              => $req->witnessMobile,
                'applied_record_id'           => $req->id,
                'version_no'                  => 0,
                'application_no'              => $penaltyRecord->application_no,
                'current_role'                => $penaltyRecord->current_role,
                'workflow_id'                 => $penaltyRecord->workflow_id,
                'ulb_id'                      => $penaltyRecord->ulb_id,
                'challan_type'                => $penaltyRecord->challan_type,
                'category_type_id'            => $penaltyRecord->category_type_id,
                'approved_by'                 => $userId,
                'guardian_name'               => $req->guardianName,
                'violation_place'             => $req->violationPlace,
                'remarks'                     => $req->remarks,
                'vehicle_no'                  => $req->vehicleNo,
            ];
            $idGeneration = new IdGeneration($challanIdParam, $penaltyRecord->ulb_id, $req->violationId, 0);
            $challanNo = $idGeneration->generate();

            DB::beginTransaction();
            $finalRecord = $mPenaltyFinalRecord->store($finalRecordReqs);
            $challanReqs = [
                'challan_no'        => $challanNo,
                'challan_date'      => Carbon::now(),
                'payment_date'      => $req->paymentDate,
                'penalty_record_id' => $finalRecord->id,
                'amount'            => $finalRecord->amount,
                'total_amount'      => $finalRecord->amount,
                'challan_type'      => $penaltyRecord->challan_type,
            ];

            $challanRecord = $mPenaltyChallan->store($challanReqs);
            $penaltyRecord->status = 2;
            $penaltyRecord->save();
            DB::commit();

            $data['id'] = $challanRecord->id;
            $data['challanNo'] = $challanRecord->challan_no;


            // $whatsapp2 = (Whatsapp_Send(
            //     $req->mobile,
            //     "rmc_fp_1",
            //     [
            //         "content_type" => "text",
            //         [
            //             $req->fullName,
            //             $challanRecord->challan_no,
            //             // section,
            //             $challanRecord->total_amount,
            //             $challanRecord->challan_date->add(14)
            //         ]
            //     ]
            // ));

            return responseMsgs(true, "", $data, "0609", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "0609", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Recent Applications
     * | API Id : 0610
     */
    public function recentApplications(Request $req)
    {
        try {
            $todayDate = now()->toDateString();
            $user = authUser($req);
            $userId = $user->id;
            $ulbId = $user->ulb_id;
            $mPenaltyRecord = new PenaltyRecord();

            $challanDtl =   $mPenaltyRecord->recordDetail()
                ->whereDate('penalty_applied_records.created_at', $todayDate)
                ->orderbyDesc('penalty_applied_records.id')
                ->take(10)
                ->get();

            return responseMsgs(true, "Recent Applications", $challanDtl, "0610", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "0610", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Recent Challans
     */
    public function recentChallans(Request $req)
    {
        try {
            $todayDate = Carbon::now();
            $mPenaltyChallan = new PenaltyChallan();
            $user = authUser($req);
            $userId = $user->id;
            $ulbId = $user->ulb_id;
            $challanDtl = $mPenaltyChallan->recentChallanDetails()
                ->where('challan_date', $todayDate)
                ->take(10)
                ->get();

            return responseMsgs(true, "Recent Challans", $challanDtl, "0611", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "0611", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Search Challans
     */
    public function searchChallan(Request $req)
    {
        try {
            $challanExpiredDate = Carbon::now()->addDay(14)->toDateString();
            $perPage = $req->perPage ?? 10;
            $mPenaltyChallan = new PenaltyChallan();
            $user = authUser($req);
            $userId = $user->id;
            $ulbId = $user->ulb_id;
            $challanDtl = $mPenaltyChallan->details();

            if ($req->challanType)
                $challanDtl = $challanDtl->where('penalty_final_records.challan_type', $req->challanType);

            $challanList = app(Pipeline::class)
                ->send($challanDtl)
                ->through([
                    SearchByApplicationNo::class,
                    SearchByMobile::class,
                    SearchByChallan::class
                ])->thenReturn()
                ->paginate($perPage);

            return responseMsgs(true, "", $challanList, "0612", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "0612", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Get Challan Details
        review again
     */
    public function challanDetails(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'challanId' => 'required|numeric'
        ]);
        if ($validator->fails())
            return validationError($validator);
        try {
            $docUrl = Config::get('constants.DOC_URL');
            $todayDate = Carbon::now();
            $mPenaltyChallan = new PenaltyChallan();
            $perPage = $req->perPage ?? 10;
            $user = authUser($req);

            $challanDtl = PenaltyChallan::select(
                'penalty_final_records.*',
                'penalty_final_records.id as application_id',
                'penalty_challans.*',
                'penalty_challans.id',
                'penalty_transactions.tran_no',
                'violations.violation_name',
                'sections.violation_section',
                // DB::raw("concat('$docUrl/',FinePenalty/cam.jpg) as geo_tagged_image"),
                DB::raw("'$docUrl/FinePenalty/cam.jpg' as geo_tagged_image"),
            )
                ->join('penalty_final_records', 'penalty_final_records.id', 'penalty_challans.penalty_record_id')
                ->leftjoin('penalty_applied_records', 'penalty_applied_records.id', 'penalty_final_records.applied_record_id')
                // ->join('penalty_applied_records as ar', 'ar.id', 'penalty_documents.applied_record_id')
                ->join('violations', 'violations.id', 'penalty_final_records.violation_id')
                // ->join('violation_sections', 'violation_sections.id', 'violations.section_id')
                ->join('sections', 'sections.id', 'violations.section_id')
                ->leftjoin('penalty_transactions', 'penalty_transactions.challan_id', 'penalty_challans.id')
                ->where('penalty_challans.id', $req->challanId)
                ->orderbyDesc('penalty_challans.id')
                ->first();

            // $finalRecord = PenaltyChallan::select('*', 'penalty_challans.id as challan_id')
            //     ->join('penalty_final_records', 'penalty_final_records.id', 'penalty_challans.penalty_record_id')
            //     ->where('penalty_challans.id', $req->challanId)
            //     ->first();

            // $appliedRecordId =  $finalRecord->applied_record_id ?? $finalRecord->id;

            // $challanDtl = PenaltyFinalRecord::select(
            //     'penalty_final_records.*',
            //     'penalty_final_records.id as application_id',
            //     'penalty_challans.*',
            //     'penalty_challans.id',
            //     DB::raw("concat('$docUrl/',document_path) as geo_tagged_image"),
            //     DB::raw(
            //         "TO_CHAR(penalty_challans.challan_date,'DD-MM-YYYY') as challan_date,
            //         TO_CHAR(penalty_challans.payment_date,'DD-MM-YYYY') as payment_date",
            //     )
            // )
            //     ->join('penalty_challans', 'penalty_challans.penalty_record_id', 'penalty_final_records.id')
            //     ->leftjoin('penalty_applied_records', 'penalty_applied_records.id', 'penalty_final_records.applied_record_id')
            //     ->leftjoin('penalty_documents', 'penalty_documents.applied_record_id', 'penalty_applied_records.id')
            //     ->join('violations', 'violations.id', 'penalty_final_records.violation_id')
            //     ->join('sections', 'sections.id', 'violations.section_id')
            //     ->where('penalty_documents.applied_record_id', $appliedRecordId)
            //     ->first();

            // $challanDtl = PenaltyChallan::select(
            //     'penalty_final_records.*',
            //     'penalty_final_records.id as application_id',
            //     'penalty_challans.*',
            //     'penalty_challans.id',
            //     'penalty_transactions.tran_no',
            //     'violations.violation_name',
            //     'sections.violation_section',
            //     DB::raw("'http://192.168.0.158:8000/FinePenalty/Documents/A03232400000125/cam.jpg' as geo_tagged_image"),
            // )
            //     ->join('penalty_final_records', 'penalty_final_records.id', 'penalty_challans.penalty_record_id')
            //     ->join('violations', 'violations.id', 'penalty_final_records.violation_id')
            //     ->join('sections', 'sections.id', 'violations.section_id')
            //     ->leftjoin('penalty_transactions', 'penalty_transactions.challan_id', 'penalty_challans.id')

            if (!$challanDtl)
                throw new Exception("No Data Found againt this challan.");

            $totalAmountInWord = getHindiIndianCurrency($challanDtl->total_amount);
            $challanDtl->amount_in_words = $totalAmountInWord . ' मात्र';

            return responseMsgs(true, "", $challanDtl, "0613", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "0613", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Offline Challan Payment
     */
    public function offlinechallanPayment(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'applicationId' => 'required|numeric',
            'challanId' => 'required|numeric',
            'paymentMode' => 'required'
        ]);
        if ($validator->fails())
            return validationError($validator);
        try {
            $mSection = new Section();
            $mViolation = new Violation();
            $mPenaltyTransaction = new PenaltyTransaction();
            $receiptIdParam = Config::get('constants.ID_GENERATION_PARAMS.RECEIPT');
            $penaltyDetails = PenaltyFinalRecord::find($req->applicationId);
            $challanDetails = PenaltyChallan::find($req->challanId);
            $todayDate = Carbon::now();
            $user = authUser($req);

            if (!$penaltyDetails)
                throw new Exception("Application Not Found");
            if ($penaltyDetails->payment_status == 1)
                throw new Exception("Payment Already Done");
            if (!$challanDetails)
                throw new Exception("Challan Not Found");

            $violationDtl  = $mViolation->violationById($penaltyDetails->violation_id);
            $sectionId     = $violationDtl->section_id;
            $section       = $mSection->sectionById($sectionId)->violation_section;
            $idGeneration  = new IdGeneration($receiptIdParam, $penaltyDetails->ulb_id, $section, 0);
            $transactionNo = $idGeneration->generate();
            $reqs = [
                "application_id" => $req->applicationId,
                "challan_id"     => $req->challanId,
                "tran_no"        => $transactionNo,
                "tran_date"      => $todayDate,
                "tran_by"        => $user->id,
                "payment_mode"   => strtoupper($req->paymentMode),
                "amount"         => $challanDetails->amount,
                "penalty_amount" => $challanDetails->penalty_amount,
                "total_amount"   => $challanDetails->total_amount,
            ];
            DB::beginTransaction();
            $tranDtl = $mPenaltyTransaction->store($reqs);
            $penaltyDetails->payment_status = 1;
            $penaltyDetails->save();

            $challanDetails->payment_date = $todayDate;
            $challanDetails->save();
            DB::commit();
            return responseMsgs(true, "", $tranDtl, "0614", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "0614", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Payment Receipt
     */
    public function paymentReceipt(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'transactionNo' => 'required',
        ]);
        if ($validator->fails())
            return validationError($validator);
        try {
            $mPenaltyTransaction = new PenaltyTransaction();
            $user = authUser($req);
            $todayDate = Carbon::now();
            $tranDtl = $mPenaltyTransaction->tranDtl()
                ->where('tran_no', $req->transactionNo)
                ->first();
            $totalAmountInWord = getHindiIndianCurrency($tranDtl->total_amount);
            $tranDtl->amount_in_words = $totalAmountInWord . ' मात्र';

            return responseMsgs(true, "", $tranDtl, "0615", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "0615", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | On Spot Challan
     */
    public function onSpotChallan(InfractionRecordingFormRequest $req)
    {
        try {
            $mSection = new Section();
            $mViolation = new Violation();
            $mPenaltyChallan = new PenaltyChallan();
            $mPenaltyDocument = new PenaltyDocument();
            $mPenaltyFinalRecord = new PenaltyFinalRecord();
            $challanIdParam = Config::get('constants.ID_GENERATION_PARAMS.CHALLAN');
            $applicationIdParam = Config::get('constants.ID_GENERATION_PARAMS.APPLICATION');
            $user = authUser();
            $ulbId = $user->ulb_id;
            $violationDtl = $mViolation->violationById($req->violationId);
            if (!$violationDtl)
                throw new Exception("Provide Valid Violation Id");
            $req->penaltyAmount = $violationDtl->penalty_amount;
            if ($req->categoryTypeId == 1)
                $req->penaltyAmount = $this->checkRickshawCondition($req);

            $sectionId     = $violationDtl->section_id;
            $section       = $mSection->sectionById($sectionId)->violation_section;
            $idGeneration  = new IdGeneration($applicationIdParam, $ulbId, $section, 0);
            $applicationNo = $idGeneration->generate();
            $metaReqs      = $this->generateRequest($req, $applicationNo);
            $metaReqs['approved_by'] = $user->id;
            $metaReqs['challan_type'] = "On Spot";

            DB::beginTransaction();
            $finalRecord =  $mPenaltyFinalRecord->store($metaReqs);
            $idGeneration = new IdGeneration($challanIdParam, $finalRecord->ulb_id, $section, 0);
            $challanNo = $idGeneration->generate();

            if ($req->file('photo')) {
                $metaReqs['documents'] = $mPenaltyDocument->storeDocument($req, $finalRecord->id, $finalRecord->application_no);
            }

            $challanReqs = [
                'challan_no'        => $challanNo,
                'challan_date'      => Carbon::now(),
                'payment_date'      => $req->paymentDate,
                'penalty_record_id' => $finalRecord->id,
                'amount'            => $finalRecord->amount,
                'total_amount'      => $finalRecord->amount,
                'challan_type'      => "On Spot",
            ];

            $challanRecord = $mPenaltyChallan->store($challanReqs);
            $data['id'] = $challanRecord->id;
            $data['challanNo'] = $challanRecord->challan_no;

            // $whatsapp2 = (Whatsapp_Send(
            //     $req->mobile,
            //     "rmc_fp_1",
            //     [
            //         "content_type" => "text",
            //         [
            //             $req->fullName,
            //             $challanRecord->challan_no,
            //             // section,
            //             $challanRecord->total_amount,
            //             $challanRecord->challan_date->add(14)
            //         ]
            //     ]
            // ));

            DB::commit();
            return responseMsgs(true, "", $data, "0616", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "0616", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Violation Wise Report
      shift query to model
     */
    public function violationData(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'fromDate' => 'required|date',
            'uptoDate' => 'required|date',
            'violationId' => 'nullable|int'
        ]);
        if ($validator->fails())
            return validationError($validator);
        try {
            $user = authUser($req);
            $perPage = $req->perPage ?? 10;
            $todayDate =  $req->date ?? now()->toDateString();
            $data = PenaltyFinalRecord::select(
                'full_name',
                'mobile',
                'violation_id',
                'violation_place',
                'challan_no',
                'violation_name',
                'sections.violation_section',
                'penalty_challans.total_amount',
                'penalty_challans.id as challan_id'
            )
                ->join('violations', 'violations.id', 'penalty_final_records.violation_id')
                ->join('sections', 'sections.id', '=', 'violations.section_id')
                ->join('penalty_challans', 'penalty_challans.penalty_record_id', 'penalty_final_records.id')
                ->whereBetween('penalty_final_records.created_at', [$req->fromDate . ' 00:00:00', $req->uptoDate . ' 23:59:59'])
                ->orderbyDesc('penalty_final_records.id');

            if ($req->violationId) {
                $data = $data->where("violation_id", $req->violationId);
            }
            $data = $data
                ->paginate($perPage);

            return responseMsgs(true, "", $data, "0617", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "0617", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Generated Challan Report
       shift query to model
     */
    public function challanData(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'fromDate'        => 'required|date',
            'uptoDate'        => 'required|date',
            'userId'          => 'nullable|int',
            'challanCategory' => 'nullable|int',
            'challanType'     => 'nullable',
        ]);
        if ($validator->fails())
            return validationError($validator);
        try {
            $user = authUser($req);
            $perPage = $req->perPage ?? 10;
            $todayDate =  $req->date ?? now()->toDateString();
            $data = PenaltyFinalRecord::select(
                'full_name',
                'penalty_final_records.mobile',
                'violation_place',
                'challan_no',
                'violation_name',
                'sections.violation_section',
                'penalty_challans.id as challan_id',
                'penalty_challans.total_amount',
                'penalty_final_records.challan_type',
                'users.name as user_name',
                'category_type as challan_category',
            )
                ->join('violations', 'violations.id', 'penalty_final_records.violation_id')
                ->join('sections', 'sections.id', '=', 'violations.section_id')
                ->join('penalty_challans', 'penalty_challans.penalty_record_id', 'penalty_final_records.id')
                ->join('users', 'users.id', 'penalty_final_records.approved_by')
                ->join('challan_categories', 'challan_categories.id', 'penalty_final_records.category_type_id')
                ->whereBetween('penalty_challans.challan_date', [$req->fromDate, $req->uptoDate])
                ->orderbyDesc('penalty_challans.id');

            if ($req->challanType)
                $data = $data->where("challan_type", $req->challanType);

            if ($req->challanCategory)
                $data = $data->where("category_type_id", $req->challanCategory);

            if ($req->userId)
                $data = $data->where("approved_by", $req->userId);

            $data = $data
                ->paginate($perPage);

            return responseMsgs(true, "", $data, "0618", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "0618", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Collection Wise Report
     shift query to model
     */
    public function collectionData(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'fromDate'        => 'required|date',
            'uptoDate'        => 'required|date',
            'paymentMode'     => 'nullable',
        ]);
        if ($validator->fails())
            return validationError($validator);
        try {
            $user = authUser($req);
            $perPage = $req->perPage ?? 10;
            $data = PenaltyTransaction::select(
                '*'
                // 'full_name',
                // 'penalty_final_records.mobile',
                // 'violation_place',
                // 'challan_no',
                // 'violation_name',
                // 'penalty_challans.total_amount',
                // 'penalty_final_records.challan_type',
            )
                ->join('penalty_final_records', 'penalty_final_records.id', 'penalty_transactions.application_id')
                ->join('violations', 'violations.id', 'penalty_final_records.violation_id')
                ->join('sections', 'sections.id', '=', 'violations.section_id')
                ->join('penalty_challans', 'penalty_challans.id', 'penalty_transactions.challan_id')
                ->whereBetween('tran_date', [$req->fromDate, $req->uptoDate]);

            if ($req->challanType)
                $data = $data->where("challan_type", $req->challanType);

            if ($req->challanCategory)
                $data = $data->where("category_type_id", $req->challanCategory);

            if ($req->userId)
                $data = $data->where("approved_by", $req->userId);

            $data = $data
                ->paginate($perPage);

            return responseMsgs(true, "", $data, "0619", "01", responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "0619", "01", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Comparison Report
     * | API Id : 0620
     */
    public function comparisonReport(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'applicationId'        => 'required',
        ]);
        if ($validator->fails())
            return validationError($validator);
        try {
            $mPenaltyRecord = new PenaltyRecord();
            $mPenaltyFinalRecord = new PenaltyFinalRecord();
            $finalRecord = $mPenaltyFinalRecord->recordDetail($req->applicationId)
                ->selectRaw('total_amount')
                ->selectRaw('users.name as user_name')
                ->join('penalty_challans', 'penalty_challans.penalty_record_id', 'penalty_final_records.id')
                ->join('users', 'users.id', 'penalty_final_records.approved_by')
                ->where('penalty_final_records.id', $req->applicationId)
                ->first();
            if (!$finalRecord)
                throw new Exception("Applied Record Not Found");

            $appliedRecord = $mPenaltyRecord->recordDetail()
                ->selectRaw('total_amount')
                ->selectRaw('users.name as user_name')
                ->join('penalty_final_records', 'penalty_final_records.applied_record_id', 'penalty_applied_records.id')
                ->join('penalty_challans', 'penalty_challans.penalty_record_id', 'penalty_final_records.id')
                ->join('users', 'users.id', 'penalty_applied_records.user_id')
                ->where('penalty_applied_records.id', $finalRecord->applied_record_id)
                ->first();
            if (!$appliedRecord)
                throw new Exception("Applied Record Not Found");

            $data = $this->comparison($finalRecord, $appliedRecord);

            return responseMsgs(true, "Comparison Report", $data, 0620, 01, responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", 0620, 01, responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Generate Request for table penalty_applied_records
        static workflow_id,ulb_id,current_role
     */
    public function generateRequest($req, $applicationNo)
    {
        return [
            'full_name'                  => $req->fullName,
            'mobile'                     => $req->mobile,
            'email'                      => $req->email,
            'holding_no'                 => $req->holdingNo,
            'street_address'             => $req->streetAddress1,
            'city'                       => $req->city,
            'region'                     => $req->region,
            'postal_code'                => $req->postalCode,
            'violation_id'               => $req->violationId,
            'amount'                     => $req->penaltyAmount,
            'previous_violation_offence' => $req->previousViolationOffence ?? 0,
            'witness'                    => $req->isWitness ?? 0,
            'witness_name'               => $req->witnessName,
            'witness_mobile'             => $req->witnessMobile,
            'application_no'             => $applicationNo,
            'current_role'               => 2,
            'workflow_id'                => 1,
            'ulb_id'                     => 2,
            'guardian_name'              => $req->guardianName,
            'violation_place'            => $req->violationPlace,
            'challan_type'               => $req->challanType,
            'category_type_id'           => $req->categoryTypeId,
        ];
    }

    /**
     * | Check Condition for E-Rickshaw
     */
    public function checkRickshawCondition($req)
    {
        $rickshawFine =  Config::get('constants.E_RICKSHAW_FINES');
        $appliedRecord =  PenaltyRecord::where('vehicle_no', $req->vehicleNo)
            ->where('status', 1)
            ->count();

        $finalRecord = PenaltyFinalRecord::where('vehicle_no', $req->vehicleNo)
            ->where('status', '<>', 1)
            ->count();

        $totalRecord = $appliedRecord + $finalRecord;

        if ($totalRecord == 5)
            throw new Exception("E-Rickshaw has been Seized");
        return $fine = $rickshawFine[$totalRecord];
    }
}
