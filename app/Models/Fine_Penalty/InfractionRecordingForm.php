<?php

namespace App\Models\Fine_Penalty;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Nette\Utils\Random;

class InfractionRecordingForm extends Model
{
    use HasFactory;

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
            'postal_code'                => $req->country,
            'country'                    => $req->country,
            'violation_id'               => $req->violationId,
            'violation_section_id'       => $req->violationSectionId,
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
        $data = InfractionRecordingForm::create($metaReqs);  // Store Record into database
        if ($req->file('photo')) {
            $ifdObj = new InfractionFormDocument();
            $metaReqs['documents'] = $ifdObj->storeDocument($req, $data->id, $applicationNo);
        }
        $appNo['application_no'] = $applicationNo;
        return $appNo;
    }

    // Check Email is Already exist or not
    public function checkExisting($req)
    {
        return InfractionRecordingForm::where('email', $req->email)
            ->first();
    }

    /*Read Records by ID*/
    public function getRecordById($id)
    {
        $data = [];
        $irfDetails =  DB::table('infraction_recording_forms as a')
            ->select(
                DB::raw("a.*,b.violation_name,c.violation_section,
            CASE WHEN a.status = '0' THEN 'Deactivated'  
            WHEN a.status = '1' THEN 'Active'
            END as status,
            TO_CHAR(a.created_at::date,'dd-mm-yyyy') as date,
            TO_CHAR(a.created_at,'HH12:MI:SS AM') as time
            ")
            )
            ->join('violations as b', 'b.id', '=', 'a.violation_id')
            ->join('violation_under_sections as c', 'c.id', '=', 'a.violation_section_id')
            ->where('a.id', $id)
            ->first();
        $data['basic_details'] = $irfDetails;
        return $data;
    }

    // Get Document Details
    public function getDocument($id)
    {
        $docUrl = "http://192.168.0.174:8000";
        $stdSibling = InfractionFormDocument::select(
            DB::raw("id,document_type,document_path,latitude,longitude,document_verified_by,document_verified_at"),
        )
            ->where('irf_id', $id)
            ->where('status', 1)
            ->get();
        if (!$stdSibling->isEmpty()) {
            foreach ($stdSibling as $v) {
                $dataArr['id'] = $v->id;
                $dataArr['document_path'] = $docUrl.'/'.$v->document_path;
                $dataArr['document_type'] = $v->document_type;
                $dataArr['latitude'] = $v->latitude;
                $dataArr['longitude'] = $v->longitude;
                $dataArr['document_verified_by'] = $v->document_verified_by;
                $dataArr['document_verified_at'] = $v->document_verified_at;
                $getDoc[] = $dataArr;
            }
        } else {
            $getDoc[] = $stdSibling;
        }
        $data['uploadDocs'] = $getDoc;
        return $data;
    }


    /*Read all Records by*/
    public function retrieve()
    {
        return DB::table('infraction_recording_forms as a')
            ->select(
                DB::raw("a.*,b.violation_name,c.violation_section,
            CASE WHEN a.status = '0' THEN 'Deactivated'  
            WHEN a.status = '1' THEN 'Active'
            END as status,
            TO_CHAR(a.created_at::date,'dd-mm-yyyy') as date,
            TO_CHAR(a.created_at,'HH12:MI:SS AM') as time
            ")
            )
            ->join('violations as b', 'b.id', '=', 'a.violation_id')
            ->join('violation_under_sections as c', 'c.id', '=', 'a.violation_section_id')
            ->orderByDesc('id');
        // ->get();
    }

    /*Read all Active Records*/
    public function active()
    {
        return DB::table('infraction_recording_forms as a')
            ->select(
                DB::raw("a.*,b.violation_name,c.violation_section,
            CASE WHEN a.status = '0' THEN 'Deactivated'  
            WHEN a.status = '1' THEN 'Active'
            END as status,
            TO_CHAR(a.created_at::date,'dd-mm-yyyy') as date,
            TO_CHAR(a.created_at,'HH12:MI:SS AM') as time
            ")
            )
            ->join('violations as b', 'b.id', '=', 'a.violation_id')
            ->join('violation_under_sections as c', 'c.id', '=', 'a.violation_section_id')
            ->where('a.status', 1)
            ->orderBy('a.id');
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
            'postal_code' => $req->country,
            'country' => $req->country,
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
        $count = InfractionRecordingForm::all()->count();
        $count++;
        $serialNo = str_pad($count, 5, '0', STR_PAD_LEFT);
        $applicationNo = 'FNP' . $randomNumber . $serialNo;
        return $applicationNo;
    }

    //Get Records by name
    public function searchByName($req)
    {
        return DB::table('infraction_recording_forms as a')

            // ->where("a.section_name", "Ilike", DB::raw("'%" . $req->search . "%'"))
            // ->orWhere("b.class_name", "Ilike", DB::raw("'%" . $req->search . "%'"))
            ->select(
                DB::raw("a.*,b.violation_name,c.violation_section,
        CASE WHEN a.status = '0' THEN 'Deactivated'  
        WHEN a.status = '1' THEN 'Active'
        END as status,
        TO_CHAR(a.created_at::date,'dd-mm-yyyy') as date,
        TO_CHAR(a.created_at,'HH12:MI:SS AM') as time
        ")
            )
            ->join('violations as b', 'b.id', '=', 'a.violation_id')
            ->join('violation_under_sections as c', 'c.id', '=', 'a.violation_section_id')
            ->where("a.application_no", "Ilike",  DB::raw("'%" . $req->applicationNo . "%'"));
        // ->where('a.school_id', $schoolId);
        // ->where('a.created_by', $createdBy);
        // ->get();
    }
}
