<?php

namespace App\Models;

use App\IdGenerator\IdGeneration;
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
        $idGeneration = new IdGeneration(1, 2);
        $applicationNo = $idGeneration->generate();
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
            'guardian_name'              => $req->guardianName,
            'violation_place'            => $req->violationPlace,
        ];
        $data = PenaltyRecord::create($metaReqs);  // Store Record into database
        return $data;
    }

    // Check Email is Already exist or not
    public function checkExisting($req)
    {
        return PenaltyRecord::where('email', $req->email)
            ->first();
    }


    /**
     * | Read Record Details
     */
    public function recordDetail()
    {
        return PenaltyRecord::select(
            'penalty_applied_records.*',
            'violations.violation_name',
            'violations.violation_section_id',
            'violation_sections.violation_section',
            'violation_sections.department',
            'violation_sections.section_definition',
            DB::raw(
                "CASE 
                        WHEN penalty_applied_records.status = '1' THEN 'Active'
                        WHEN penalty_applied_records.status = '0' THEN 'Deactivated'  
                        WHEN penalty_applied_records.status = '2' THEN 'Approved'  
                    END as status,
                    TO_CHAR(penalty_applied_records.created_at::date,'dd-mm-yyyy') as date,
                    TO_CHAR(penalty_applied_records.created_at,'HH12:MI:SS AM') as time"
            )
        )
            ->join('violations', 'violations.id', '=', 'penalty_applied_records.violation_id')
            ->join('violation_sections', 'violation_sections.id', '=', 'violations.violation_section_id')
            ->orderBy('penalty_applied_records.id');
    }

    /**
     * | Get Records by Application No
     */
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
