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
            DB::raw("violations.id,violations.violation_name,violations.penalty_amount,
            sections.violation_section, departments.department_name,
        CASE 
            WHEN violations.status = '0' THEN 'Deactivated'  
            WHEN violations.status = '1' THEN 'Active'
        END as status,
        TO_CHAR(violations.created_at::date,'dd-mm-yyyy') as date,
        TO_CHAR(violations.created_at,'HH12:MI:SS AM') as time
        ")
        )
        ->join('sections' , 'sections.id', '=', 'violations.section_id')
        ->join('departments' , 'departments.id', '=', 'violations.department_id')
        ->where('violations.id', $id)
        ->first();
    }

    /*Read all Records by*/
    public function retrieve()
    {
        return Violation::select(
            DB::raw("violations.id,violations.violation_name,violations.penalty_amount, 
            sections.violation_section, departments.department_name,
        CASE 
            WHEN violations.status = '0' THEN 'Deactivated'  
            WHEN violations.status = '1' THEN 'Active'
        END as status,
        TO_CHAR(violations.created_at::date,'dd-mm-yyyy') as date,
        TO_CHAR(violations.created_at,'HH12:MI:SS AM') as time
        ")
        )
        ->join('sections' , 'sections.id', '=', 'violations.section_id')
        ->join('departments' , 'departments.id', '=', 'violations.department_id')
        ->where('violations.status', 1)
        ->orderByDesc('violations.id')
        ->get();
    }

    /*Read all Records by sectionId and DepartmentId*/
    public function getList($req)
    {
        return Violation::select(
            DB::raw("id,violation_name,penalty_amount,section_id,department_id,
        CASE 
            WHEN status = '0' THEN 'Deactivated'  
            WHEN status = '1' THEN 'Active'
        END as status,
        TO_CHAR(created_at::date,'dd-mm-yyyy') as date,
        TO_CHAR(created_at,'HH12:MI:SS AM') as time
        ")
        )
        ->where('section_id',$req->sectionId)
        ->where('department_id',$req->departmentId)
        ->where('status', 1)
        ->orderByDesc('id')
        ->get();
    }
}
