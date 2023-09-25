<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PenaltyRecord extends Model
{
    use HasFactory;

    protected $table = "penalty_applied_records";
    protected $guarded = [];

    /*Add Records*/
    public function store($req)
    {
        $applicationNo = $this->generateApplicationNumber(); // Generate Application Number
        // Store all documents and values in $metaReqs array
        $metaReqs = [
            'full_name'                  => $req->fullName,
            'mobile'                     => $req->mobile,
            'email'                      => $req->email,
            'holding_no'                 => $req->holdingNo,
            'street_address'             => $req->streetAddress1,
            'street_address_2'           => $req->streetAddress2,
            'city'                       => $req->city,
            'region'                     => $req->region,
            'postal_code'                => $req->postalCode,
            'violation_id'               => $req->violationId,
            // 'violation_section_id'       => $req->violationSectionId,
            'penalty_amount'             => $req->penaltyAmount,
            'previous_violation_offence' => $req->previousViolationOffence ?? 0,
            'witness'                    => $req->isWitness ?? 0,
            'witness_name'               => $req->witnessName,
            'witness_mobile'             => $req->witnessMobile,
            'application_no'             => $applicationNo,
            'current_role'               => 2,
            'workflow_id'                => 1,
            'ulb_id'                     => 2,
        ];
        $data = PenaltyRecord::create($metaReqs);  // Store Record into database
        if ($req->file('photo')) {
            $ifdObj = new PenaltyDocument();
            $metaReqs['documents'] = $ifdObj->storeDocument($req, $data->id, $applicationNo);
        }
        $appNo['application_no'] = $applicationNo;
        return $appNo;
    }

    // Check Email is Already exist or not
    public function checkExisting($req)
    {
        return PenaltyRecord::where('email', $req->email)
            ->first();
    }

    /*Read Records by ID*/
    public function getRecordById($id)
    {
        $data = [];
        $data =  DB::table('penalty_applied_records')
            ->select(

                DB::raw("penalty_applied_records.*,b.violation_name,violation_section,
            CASE WHEN penalty_applied_records.status = '0' THEN 'Deactivated'  
            WHEN penalty_applied_records.status = '1' THEN 'Active'
            END as status,
            TO_CHAR(penalty_applied_records.created_at::date,'dd-mm-yyyy') as date,
            TO_CHAR(penalty_applied_records.created_at,'HH12:MI:SS AM') as time
            ")
            )
            ->join('violations as b', 'b.id', '=', 'penalty_applied_records.violation_id')
            ->where('penalty_applied_records.id', $id)
            ->first();
        // $data['basic_details'] = $irfDetails;
        return $data;
    }



    /**
     * | Read all Active Records
     */
    public function active()
    {
        return PenaltyRecord::select(
            'penalty_applied_records.*',
            'violations.violation_name',
            'violations.violation_section_id',
            DB::raw(
                "CASE 
                        WHEN penalty_applied_records.status = '1' THEN 'Active'
                        WHEN penalty_applied_records.status = '0' THEN 'Deactivated'  
                    END as status,
                    TO_CHAR(penalty_applied_records.created_at::date,'dd-mm-yyyy') as date,
                    TO_CHAR(penalty_applied_records.created_at,'HH12:MI:SS AM') as time"
            )
        )
            ->join('violations', 'violations.id', '=', 'penalty_applied_records.violation_id')
            ->where('penalty_applied_records.status', 1)
            ->orderBy('penalty_applied_records.id');
    }

    /**
     * | Update the details
     */
    public function edit($req, $getData)
    {
        $metaReqs = [];
        // $metaReqs['photo'] = $req->photo ? 'FinePenalty/Documents/'.$req->mobile.'-'.$req->photo->getClientOriginalName() : $getData->photo;
        // if ($req->hasFile('photo')) {
        //     $req->file('photo')->move(public_path('FinePenalty/Documents/'.$req->mobile), $req->photo->getClientOriginalName());
        // }

        // $metaReqs['video_audio'] = $req->audioVideo ? 'FinePenalty/Documents/'.$req->mobile.'-'.$req->audioVideo->getClientOriginalName() : $getData->video_audio;
        // if ($req->hasFile('audioVideo')) {
        //     $req->file('audioVideo')->move(public_path('FinePenalty/Documents/'.$req->mobile), $req->audioVideo->getClientOriginalName());
        // }

        // $metaReqs['pdf'] = $req->pdf ? 'FinePenalty/Documents/'.$req->mobile.'-'.$req->pdf->getClientOriginalName() : $getData->pdf;
        // if ($req->hasFile('pdf')) {
        //     $req->file('pdf')->move(public_path('FinePenalty/Documents/'.$req->mobile), $req->pdf->getClientOriginalName());
        // }

        $metaReqs = array_merge($metaReqs, [
            'full_name'                 => $req->fullName,
            'mobile' => $req->mobile,
            'email' => $req->email,
            'holding_no' => $req->holdingNo,
            'street_address' => $req->streetAddress,
            'street_address_2' => $req->streetAddress2,
            'city' => $req->city,
            'region' => $req->region,
            'postal_code' => $req->postalCode,
            'violation_id' => $req->violationId,
            'violation_section_id' => $req->violationSectionId,
            'penalty_amount' => $req->penaltyAmount,
            'previous_violation_offence' => $req->previousViolationOffence,
            'witness' => $req->witness,
            'witness_name' => $req->witnessName,
            'witness_mobile' => $req->witnessMobile,
            'version_no' => $getData->version_no + 1,
            'updated_at' => Carbon::now()
        ]);
        $getData->update($metaReqs);
        return $metaReqs;
    }

    function generateApplicationNumber()
    {
        $randomNumber  = str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);
        $count = PenaltyRecord::all()->count();
        $count++;
        $serialNo = str_pad($count, 5, '0', STR_PAD_LEFT);
        $applicationNo = 'FNP' . $randomNumber . $serialNo;
        return $applicationNo;
    }

    //Get Records by name
    public function searchByName($req)
    {
        return DB::table('penalty_applied_records as a')

            // ->where("penalty_applied_records.section_name", "Ilike", DB::raw("'%" . $req->search . "%'"))
            // ->orWhere("b.class_name", "Ilike", DB::raw("'%" . $req->search . "%'"))
            ->select(
                DB::raw("penalty_applied_records.*,b.violation_name,c.violation_section,
        CASE WHEN penalty_applied_records.status = '0' THEN 'Deactivated'  
        WHEN penalty_applied_records.status = '1' THEN 'Active'
        END as status,
        TO_CHAR(penalty_applied_records.created_at::date,'dd-mm-yyyy') as date,
        TO_CHAR(penalty_applied_records.created_at,'HH12:MI:SS AM') as time
        ")
            )
            ->join('violations as b', 'b.id', '=', 'penalty_applied_records.violation_id')
            ->join('violation_under_sections as c', 'c.id', '=', 'penalty_applied_records.violation_section_id')
            ->where("penalty_applied_records.application_no", "Ilike",  DB::raw("'%" . $req->applicationNo . "%'"));
    }
}
