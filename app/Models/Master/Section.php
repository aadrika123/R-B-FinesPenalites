<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Section extends Model
{
    use HasFactory;

    protected $guarded = [];

    /*Add Records*/
    public function store(array $req)
    {
        return Section::create($req);
    }

     /*Read all Records by*/
     public function getList($req)
     {
         return Section::select(
             DB::raw("id,section,department_id,
         CASE 
             WHEN status = '0' THEN 'Deactivated'  
             WHEN status = '1' THEN 'Active'
         END as status,
         TO_CHAR(created_at::date,'dd-mm-yyyy') as date,
         TO_CHAR(created_at,'HH12:MI:SS AM') as time
         ")
         )
         ->where('department_id',$req->departmentId)
         ->where('status', 1)
         ->orderByDesc('id')
         ->get();
     }
}
