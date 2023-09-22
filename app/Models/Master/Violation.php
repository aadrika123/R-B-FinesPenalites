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
        DB::raw("id,violation_name,
        CASE 
            WHEN status = '0' THEN 'Deactivated'  
            WHEN status = '1' THEN 'Active'
        END as status,
        TO_CHAR(created_at::date,'dd-mm-yyyy') as date,
        TO_CHAR(created_at,'HH12:MI:SS AM') as time
        ")
        )
        ->where('id', $id)
        ->first();
    }

    /*Read all Records by*/
    public function retrieve()
    {
        return Violation::select(
        DB::raw("id,violation_name,violation_section,penalty_amount,
        CASE 
            WHEN status = '0' THEN 'Deactivated'  
            WHEN status = '1' THEN 'Active'
        END as status,
        TO_CHAR(created_at::date,'dd-mm-yyyy') as date,
        TO_CHAR(created_at,'HH12:MI:SS AM') as time
        ")
        )
        ->where('status', 1)
        ->orderByDesc('id')
        ->get();
    }
}
