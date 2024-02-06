<?php

namespace App\Http\Controllers;

use App\Models\PenaltyChallan;
use App\Models\PenaltyFinalRecord;
use App\Models\PenaltyRecord;
use App\Models\PenaltyTransaction;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeactivationController extends Controller
{

    /**
     * | Deactivate Application
     */
    public function deactivateApplication(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            ["applicationId" => "required"]
        );

        if ($validator->fails())
            return validationError($validator);
        try {
            $penaltyDtls = PenaltyFinalRecord::find($request->applicationId);
            $penaltyDtls->status = 0;
            $penaltyDtls->save();

            if ($penaltyDtls->applied_record_id) {
                $penaltyAppliedRecord = PenaltyRecord::find($penaltyDtls->applied_record_id);
                $penaltyAppliedRecord->status = 0;
                $penaltyAppliedRecord->save();
            }

            return responseMsgs(true, "Application Deactivated", [], "1001", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "1001", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }

    /**
     * | Deactivate Challan
     */
    public function deactivateChallan(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            ["challanId" => "required"]
        );

        if ($validator->fails())
            return validationError($validator);
        try {
            $challanDtls = PenaltyChallan::find($request->challanId);
            $challanDtls->status = 0;
            $challanDtls->save();

            return responseMsgs(true, "Challan Deactivated", [], "1002", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "1002", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }

    /**
     * | Deactivate Payment
     */
    public function deactivatePayment(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            ["tranNo" => "required"]
        );

        if ($validator->fails())
            return validationError($validator);
        try {
            $tranDtls = PenaltyTransaction::where('tran_no', $request->tranNo)
                ->first();
            $tranDtls->status = 0;
            $tranDtls->save();

            $challanDtls = PenaltyChallan::find($tranDtls->challan_id);
            $challanDtls->payment_date = null;
            $challanDtls->save();

            $penaltyDtls = PenaltyFinalRecord::find($tranDtls->application_id);
            $penaltyDtls->payment_status = false;
            $penaltyDtls->save();

            return responseMsgs(true, "Payment Deactivated", [], "1003", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "1003", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }
}
