<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ViolationUnderSection extends Model
{
    use HasFactory;

    protected $table = "violation_details";
    protected $guarded = [];

    /*Add Records*/
    public function store(array $req)
    {
        ViolationUnderSection::create($req);
    }

    /*Read Records by name*/
    public function checkExisting($req)
    {
        return ViolationUnderSection::where('violation_id', $req->violationId)
            ->where(DB::raw('upper(violation_section)'), strtoupper($req->violationSection))
            ->where('status', 1)
            ->get();
    }

    /*Read Records by ID*/
    public function getRecordById($id)
    {
        return DB::table('violation_under_sections as a')
            ->select(
                DB::raw("a.id,a.violation_section,a.violation_id,b.violation_name, a.penalty_amount,
            CASE WHEN a.status = '0' THEN 'Deactivated'  
            WHEN a.status = '1' THEN 'Active'
            END as status,
            TO_CHAR(a.created_at::date,'dd-mm-yyyy') as date,
            TO_CHAR(a.created_at,'HH12:MI:SS AM') as time
            ")
            )
            ->join('violations as b', 'b.id', '=', 'a.violation_id')
            ->where('a.id', $id)
            ->first();
    }

    /*Read all Records by*/
    public function retrieve()
    {
        return DB::table('violation_under_sections as a')
            ->select(
                DB::raw("a.id,a.violation_section,a.violation_id,b.violation_name,a.penalty_amount,
            CASE WHEN a.status = '0' THEN 'Deactivated'  
            WHEN a.status = '1' THEN 'Active'
            END as status,
            TO_CHAR(a.created_at::date,'dd-mm-yyyy') as date,
            TO_CHAR(a.created_at,'HH12:MI:SS AM') as time
            ")
            )
            ->join('violations as b', 'b.id', '=', 'a.violation_id')
            ->get();
    }
}
