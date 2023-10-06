<?php

namespace App\Http\Controllers\Penalty;

use App\Http\Controllers\Controller;
use App\Models\IdGenerationParam;
use App\Models\Payment\CcAvenueReq;
use App\Models\Payment\CcAvenueResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
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

            $user = authUser();
            $mReqs = [
                "ref_no"          => $this->getOrderId(),
                "user_id"         => $user->id,
                "workflow_id"     => $req->workflowId ?? 0,
                "amount"          => $req->amount,
                "ulb_id"          => $user->ulb_id ?? $req->ulbId,
                "application_id"  => $req->applicationId,
                "payment_type"    => $req->paymentType

            ];
            $data = $mCcAvenueReq->store($mReqs);

            return responseMsgs(true, "Bill id is", ['billRefNo' => $data->ref_no], 0701, 01, responseTime(), $req->getMethod(), $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", 0701, 01, responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Save Pine lab Response
     */
    public function savePinelabResponse(Request $req)
    {
        $idGeneration = new IdGenerationParam();
        try {
            Storage::disk('public')->put($req->billRefNo . '.json', json_encode($req->all()));
            $mCcAvenueReq      =  new CcAvenueReq();
            $mCcAvenueResponse = new CcAvenueResponse();
            $responseCode            = Config::get('payment-constants.PINELAB_RESPONSE_CODE');
            $user                    = authUser();
            $pinelabData             = $req->pinelabResponseBody;
            $detail                  = (object)($req->pinelabResponseBody['Detail'] ?? []);

            $actualTransactionNo = $idGeneration->generateTransactionNo($user->ulb_id);
            $paymentData = $mCcAvenueReq->getPaymentRecord($req);

            if (collect($paymentData)->isEmpty())
                throw new Exception("Payment Data not available");
            if ($paymentData) {
                $mReqs = [
                    "payment_req_id"       => $paymentData->id,
                    "req_ref_no"           => $req->billRefNo,
                    "res_ref_no"           => $actualTransactionNo,                         // flag
                    "response_msg"         => $pinelabData['Response']['ResponseMsg'],
                    "response_code"        => $pinelabData['Response']['ResponseCode'],
                    "description"          => $req->description,
                ];

                $data = $mCcAvenueResponse->store($mReqs);
            }

            # data transfer to the respective module's database 
            $moduleData = [
                'id'                => $req->applicationId,
                'billRefNo'         => $req->billRefNo,
                'amount'            => $req->amount,
                'workflowId'        => $req->workflowId,
                'userId'            => $user->id,
                'ulbId'             => $user->ulb_id,
                'gatewayType'       => "Pinelab",         #_Pinelab Id
                'transactionNo'     => $actualTransactionNo,
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
                switch ($paymentData->module_id) {
                    case ('1'):
                        $workflowId = $paymentData->workflow_id;
                        if ($workflowId == 0) {
                            $objHoldingTaxController = new HoldingTaxController($this->_safRepo);
                            $moduleData = new Request($moduleData);
                            $objHoldingTaxController->paymentHolding($moduleData);
                        } else {                                            //<------------------ (SAF PAYMENT)
                            $obj = new ActiveSafController($this->_safRepo);
                            $moduleData = new ReqPayment($moduleData);
                            $obj->paymentSaf($moduleData);
                        }
                        break;
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
        $orderId = explode("=", chunk_split($orderId, 30, "="))[0];
        return $orderId;
    }
}
