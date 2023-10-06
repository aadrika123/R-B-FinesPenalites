<?php

namespace App\Http\Controllers\Penalty;

use App\Http\Controllers\Controller;
use App\IdGenerator\IdGeneration;
use App\Models\IdGenerationParam;
use App\Models\Master\Section;
use App\Models\Master\Violation;
use App\Models\Payment\CcAvenueReq;
use App\Models\Payment\CcAvenueResponse;
use App\Models\PenaltyChallan;
use App\Models\PenaltyFinalRecord;
use App\Models\PenaltyTransaction;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

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
     * | Save Pine lab Request
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
            $mCcAvenueReq =  new CcAvenueReq();
            $penaltyDetails = PenaltyFinalRecord::find($req->applicationId);
            $challanDetails = PenaltyChallan::find($req->challanId);
            if (!$penaltyDetails)
                throw new Exception("Application Not Found");
            if ($penaltyDetails->payment_status == 1)
                throw new Exception("Payment Already Done");
            if (!$challanDetails)
                throw new Exception("Challan Not Found");

            $user  = authUser();
            $mReqs = [
                "order_id"       => $this->getOrderId(),
                "merchant_id"    => $req->merchantId,
                "challan_id"     => $req->challanId,
                "application_id" => $req->applicationId,
                "user_id"        => $user->id,
                "workflow_id"    => $penaltyDetails->workflow_id ?? 0,
                "amount"         => $challanDetails->total_amount,
                "ulb_id"         => $user->ulb_id ?? $penaltyDetails->ulb_id,
                "ip_address"     => getClientIpAddress()
            ];
            $data = $mCcAvenueReq->store($mReqs);

            return responseMsgs(true, "Order id is", ['order_id' => $data->order_id], "0701", 01, responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "0701", 01, responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Save Pine lab Response
     */
    public function savePinelabResponse(Request $req)
    {
        $idGeneration = new IdGenerationParam();
        try {
            Storage::disk('public')->put($req->order_id . '.json', json_encode($req->all()));
            $mSection            = new Section();
            $mViolation          = new Violation();
            $mCcAvenueReq        = new CcAvenueReq();
            $mCcAvenueResponse   = new CcAvenueResponse();
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

            $paymentData = $mCcAvenueReq->getPaymentRecord($req);

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
                    "res_ref_no"      => $transactionNo,                         // flag
                    "response_msg"    => $pinelabData['Response']['ResponseMsg'],
                    "response_code"   => $pinelabData['Response']['ResponseCode'],
                    "description"     => $req->description,
                ];

                $data = $mCcAvenueResponse->store($mReqs);
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

    /**
     * | Generate Order Id
     */
    protected function getOrderId()
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < 10; $i++) {
            $index = rand(0, strlen($characters) - 1);
            $randomString .= $characters[$index];
        }
        $orderId = (("Order_" . date('dmyhism') . $randomString));
        $orderId = explode("=", chunk_split($orderId, 26, "="))[0];
        return $orderId;
    }
}
