<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Violation extends Model
{
    use HasFactory;

    protected $guarded = [];

    /*Add Records*/
    public function store(array $req)
    {
        return Violation::create($req);
    }

    /*Read Records by name*/
    public function checkExisting($req)
    {
        return Violation::where(DB::raw('upper(violation_name)'), strtoupper($req->violationName))
            ->where('status', 1)
            ->get();
    }

    /*Read Records by ID*/
    public function getRecordById($id)
    {
        return Violation::select(
            DB::raw("violations.id,violations.violation_name,violations.penalty_amount,violations.violation_section_id,
            violation_sections.violation_section,violation_sections.department,violation_sections.section_definition,
        CASE 
            WHEN violations.status = '0' THEN 'Deactivated'  
            WHEN violations.status = '1' THEN 'Active'
        END as status,
        TO_CHAR(violations.created_at::date,'dd-mm-yyyy') as date,
        TO_CHAR(violations.created_at,'HH12:MI:SS AM') as time
        ")
        )
        ->join('violation_sections' , 'violation_sections.id', '=', 'violations.violation_section_id')
            ->where('violations.id', $id)
            ->first();
    }

    /*Read all Records by*/
    public function retrieve()
    {
        return Violation::select(
            DB::raw("violations.id,violations.violation_name,violations.penalty_amount,violations.violation_section_id,
            violation_sections.violation_section,violation_sections.department,violation_sections.section_definition,
        CASE 
            WHEN violations.status = '0' THEN 'Deactivated'  
            WHEN violations.status = '1' THEN 'Active'
        END as status,
        TO_CHAR(violations.created_at::date,'dd-mm-yyyy') as date,
        TO_CHAR(violations.created_at,'HH12:MI:SS AM') as time
        ")
        )
        ->join('violation_sections' , 'violation_sections.id', '=', 'violations.violation_section_id')
            ->where('violations.status', 1)
            ->orderByDesc('violations.id')
            ->get();
    }
}
