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
        $data = PenaltyRecord::create($req);  // Store Record into database
        return $data;
    }

    /**
     * | Read Record Details
     */
    public function recordDetail()
    {
        return PenaltyRecord::select(
            'penalty_applied_records.*',
            'violations.violation_name',
            'violations.section_id',
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
            ->join('violation_sections', 'violation_sections.id', '=', 'violations.section_id')
            ->orderByDesc('penalty_applied_records.id');
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
