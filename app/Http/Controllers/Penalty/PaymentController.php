<?php

namespace App\Http\Controllers\Penalty;

use App\Http\Controllers\Controller;
use App\IdGenerator\IdGeneration;
use App\Models\IdGenerationParam;
use App\Models\PenaltyChallan;
use App\Models\PenaltyFinalRecord;
use App\Models\PenaltyTransaction;
use App\Models\Master\Section;
use App\Models\Master\Violation;
use App\Models\Payment\RazorpayReq;
use App\Models\Payment\RazorpayResponse;
use App\Models\PenaltyDailycollection;
use App\Models\PenaltyDailycollectiondetail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Exception;
use Razorpay\Api\Api;

/**
 * =======================================================================================================
 * ===================         Created By : Mrinal Kumar        ==========================================
 * ===================         Created On : 06-10-2023          ==========================================
 * =======================================================================================================
 * | Status : Open
 */

class PaymentController extends Controller
{

    /**
     * | Save Razor Pay Request
     */
    public function initiatePayment(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "amount"        => "required|numeric",
            "challanId"     => "required|int",
            "applicationId" => "required|int",
            "workflowId"    => "nullable|int",
        ]);

        if ($validator->fails())
            return validationError($validator);

        try {

            $keyId        = Config::get('constants.RAZORPAY_KEY');
            $secret       = Config::get('constants.RAZORPAY_SECRET');
            $mRazorpayReq = new RazorpayReq();
            $api          = new Api($keyId, $secret);

            $penaltyDetails = PenaltyFinalRecord::find($req->applicationId);
            $challanDetails = PenaltyChallan::find($req->challanId);
            if (!$penaltyDetails)
                throw new Exception("Application Not Found");
            if ($penaltyDetails->payment_status == 1)
                throw new Exception("Payment Already Done");
            if (!$challanDetails)
                throw new Exception("Challan Not Found");

            $orderData = $api->order->create(array('amount' => $challanDetails->total_amount * 100, 'currency' => 'INR',));
            $user  = authUser();
            $mReqs = [
                "order_id"       => $orderData['id'],
                "merchant_id"    => $req->merchantId,
                "challan_id"     => $req->challanId,
                "application_id" => $req->applicationId,
                "user_id"        => $user->id,
                "workflow_id"    => $penaltyDetails->workflow_id ?? 0,
                "amount"         => $challanDetails->total_amount,
                "ulb_id"         => $user->ulb_id ?? $penaltyDetails->ulb_id,
                "ip_address"     => getClientIpAddress()
            ];
            $data = $mRazorpayReq->store($mReqs);

            return responseMsgs(true, "Order id is", ['order_id' => $data->order_id], "0701", 01, responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "0701", 01, responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Save Razor Pay Response
     */
    public function saveRazorpayResponse(Request $req)
    {
        $idGeneration = new IdGenerationParam();
        try {
            Storage::disk('public')->put($req->order_id . '.json', json_encode($req->all()));
            $mSection            = new Section();
            $mViolation          = new Violation();
            $mRazorpayReq        = new RazorpayReq();
            $mRazorpayResponse   = new RazorpayResponse();
            $mPenaltyTransaction = new PenaltyTransaction();
            $todayDate           = Carbon::now();
            $penaltyDetails    = PenaltyFinalRecord::find($req->applicationId);
            $receiptIdParam    = Config::get('constants.ID_GENERATION_PARAMS.RECEIPT');
            $responseCode      = Config::get('payment-constants.PINELAB_RESPONSE_CODE');
            $user              = authUser();
            $pinelabData       = $req->pinelabResponseBody;
            $detail            = (object)($req->pinelabResponseBody['Detail'] ?? []);


            $violationDtl  = $mViolation->violationById($penaltyDetails->violation_id);
            $sectionId     = $violationDtl->section_id;
            $section       = $mSection->sectionById($sectionId)->violation_section;
            $idGeneration  = new IdGeneration($receiptIdParam, $penaltyDetails->ulb_id, $section, 0);
            $transactionNo = $idGeneration->generate();

            $paymentData = $mRazorpayReq->getPaymentRecord($req);

            if (collect($paymentData)->isEmpty())
                throw new Exception("Payment Data not available");
            if ($paymentData) {
                $mReqs = [
                    "request_id"      => $paymentData->id,
                    "order_id"        => $req->orderId,
                    "merchant_id"     => $req->merchantId,
                    "payment_id"      => $req->paymentId,
                    "challan_id"      => $req->challanId,
                    "application_id"  => $req->applicationId,
                    // "res_ref_no"      => $transactionNo,                         // flag
                    // "response_msg"    => $pinelabData['Response']['ResponseMsg'],
                    // "response_code"   => $pinelabData['Response']['ResponseCode'],
                    "description"     => $req->description,
                ];

                $data = $mRazorpayResponse->store($mReqs);
            }

            # data transfer to the respective module's database 
            $moduleData = [
                'id'                => $req->applicationId,
                'order_id'          => $req->order_id,
                'amount'            => $req->amount,
                'workflowId'        => $req->workflowId,
                'userId'            => $user->id,
                'ulbId'             => $user->ulb_id,
                'gatewayType'       => "CC AVENUE",         #_CcAvenue Id
                'transactionNo'     => $transactionNo,
                'TransactionDate'   => $detail->TransactionDate ?? null,
                'HostResponse'      => $detail->HostResponse ?? null,
                'CardEntryMode'     => $detail->CardEntryMode ?? null,
                'ExpiryDate'        => $detail->ExpiryDate ?? null,
                'InvoiceNumber'     => $detail->InvoiceNumber ?? null,
                'MerchantAddress'   => $detail->MerchantAddress ?? null,
                'TransactionTime'   => $detail->TransactionTime ?? null,
                'TerminalId'        => $detail->TerminalId ?? null,
                'TransactionType'   => $detail->TransactionType ?? null,
                'CardNumber'        => $detail->CardNumber ?? null,
                'MerchantId'        => $detail->MerchantId ?? null,
                'PlutusVersion'     => $detail->PlutusVersion ?? null,
                'PosEntryMode'      => $detail->PosEntryMode ?? null,
                'RetrievalReferenceNumber' => $detail->RetrievalReferenceNumber ?? null,
                'BillingRefNo'             => $detail->BillingRefNo ?? null,
                'BatchNumber'              => $detail->BatchNumber ?? null,
                'Remark'                   => $detail->Remark ?? null,
                'AcquiringBankCode'        => $detail->AcquiringBankCode ?? null,
                'MerchantName'             => $detail->MerchantName ?? null,
                'MerchantCity'             => $detail->MerchantCity ?? null,
                'ApprovalCode'             => $detail->ApprovalCode ?? null,
                'CardType'                 => $detail->CardType ?? null,
                'PrintCardholderName'      => $detail->PrintCardholderName ?? null,
                'AcquirerName'             => $detail->AcquirerName ?? null,
                'LoyaltyPointsAwarded'     => $detail->LoyaltyPointsAwarded ?? null,
                'CardholderName'           => $detail->CardholderName ?? null,
                'AuthAmoutPaise'           => $detail->AuthAmoutPaise ?? null,
                'PlutusTransactionLogID'   => $detail->PlutusTransactionLogID ?? null
            ];


            if ($pinelabData['Response']['ResponseCode'] == 00) {                           // Success Response code(00)
                $paymentData->payment_status = 1;
                $paymentData->save();

                # calling function for the modules
                if ($paymentData->module_id) {
                    $reqs = [
                        "application_id" => $req->applicationId,
                        "challan_id"     => $req->challanId,
                        "tran_no"        => $transactionNo,
                        "tran_date"      => $todayDate,
                        "tran_by"        => $user->id,
                        "payment_mode"   => strtoupper('ONLINE'),
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
                }
            } else
                throw new Exception("Payment Cancelled");
            return responseMsgs(true, "Data Saved", $data, 0702, 01, responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", 0702, 01, responseTime(), $req->getMethod(), $req->deviceId);
        }
    }



    #=================================================================================================================================
    #==============================================           CASH VERIFICATION          =============================================
    #=================================================================================================================================

    /**
     * | Unverified Cash Verification List
     */
    public function listCashVerification(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'date' => 'required|date',
            'userId' => 'nullable|int'
        ]);
        if ($validator->fails())
            return validationError($validator);
        try {
            $mPenaltyTransaction = new PenaltyTransaction();
            $userId =  $req->userId;
            $date = date('Y-m-d', strtotime($req->date));

            if (isset($userId)) {
                $data = $mPenaltyTransaction->cashDtl($date)
                    ->where('user_id', $userId)
                    ->get();
            }

            if (!isset($userId)) {
                $data = $mPenaltyTransaction->cashDtl($date)
                    ->get();
            }

            $collection = collect($data->groupBy("user_id")->values());

            $data = $collection->map(function ($val) use ($date) {
                $total =  $val->sum('total_amount');
                return [
                    "id" => $val[0]['id'],
                    "user_id" => $val[0]['user_id'],
                    "officer_name" => $val[0]['user_name'],
                    "mobile" => $val[0]['mobile'],
                    "penalty_amount" => $total,
                    "date" => Carbon::parse($date)->format('d-m-Y'),
                ];
            });

            return responseMsgs(true, "Cash Verification List", $data, "010201", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010201", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }
    /**
     * | Tc Collection Dtl
     */
    public function cashVerificationDtl(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "date" => "required|date",
            "userId" => "required|int",
        ]);
        if ($validator->fails())
            return validationError($validator);
        try {
            $mPenaltyTransaction = new PenaltyTransaction();
            $userId =  $req->userId;
            $date = date('Y-m-d', strtotime($req->date));
            $details = $mPenaltyTransaction->cashDtl($date, $userId)
                ->where('tran_by', $userId)
                ->get();

            if (collect($details)->isEmpty())
                throw new Exception("No Application Found for this id");

            $data['tranDtl'] = collect($details)->values();
            $data['Cash'] = collect($details)->where('payment_mode', 'CASH')->sum('total_amount');
            $data['totalAmount'] =  $details->sum('total_amount');
            $data['numberOfTransaction'] =  $details->count();
            $data['date'] = Carbon::parse($date)->format('d-m-Y');

            return responseMsgs(true, "Cash Verification Details", remove_null($data), "010201", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "010201", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }
    /**
     * | For Verification of cash
        save data in collection detail is pending and update verify status in transaction table
     */
    public function verifyCash(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "date"          => "required|date",
            "tcId"          => "required|int",
            "depositAmount" => "required|numeric",
        ]);
        if ($validator->fails())
            return validationError($validator);
        try {
            $user = authUser($req);
            $userId = $user->id;
            $ulbId = $user->ulb_id;
            $mPenaltyDailycollection       = new PenaltyDailycollection();
            $mPenaltyDailycollectiondetail = new PenaltyDailycollectiondetail();
            $receiptIdParam                = Config::get('constants.ID_GENERATION_PARAMS.CASH_VERIFICATION_ID');
            DB::beginTransaction();
            $idGeneration  = new IdGeneration($receiptIdParam, $user->ulb_id, 000, 0);
            $receiptNo = $idGeneration->generate();

            $mReqs = [
                "receipt_no"     => $receiptNo,
                "user_id"        => $userId,
                "tran_date"      => $req->date,
                "deposit_date"   => Carbon::now(),
                "deposit_amount" => $req->depositAmount,
                "tc_id"          => $req->tcId,
            ];

            $collectionId =  $mPenaltyDailycollection->store($mReqs);

            DB::commit();
            return responseMsgs(true, "Cash Verified", ["receipt_no" => $receiptNo], "010201", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "010201", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }
}
