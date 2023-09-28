<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class PenaltyDocument extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function storeDocument($req, $id, $applicationNo)
    {
        if ($req->file('photo')) {
            $docPath = $req->file('photo')->move(public_path('FinePenalty/'), $req->photo->getClientOriginalName());
            $file_name = 'FinePenalty/' . $req->photo->getClientOriginalName();
            $docType = $req->photo->getClientOriginalExtension();
            // Create a new PhotoMetadata record
            $docMetadata = new PenaltyDocument([
                'applied_record_id' => $id,
                'document_type' => $docType,
                'document_path' => $file_name,
                'document_name' => "Violation Image",
                'latitude' => $req->latitude,
                'longitude' => $req->longitude,
                'document_verified_by' => authUser()->id,
                'document_verified_at' => Carbon::now(),
            ]);
            $docMetadata->save();
            $data['photo_details'] = $docMetadata;
        }

        if ($req->file('audioVideo')) {
            $docPath = $req->file('audioVideo')->move(public_path('FinePenalty/'), $req->audioVideo->getClientOriginalName());
            $file_name = 'FinePenalty/' . $req->audioVideo->getClientOriginalName();
            $docType = $req->audioVideo->getClientOriginalExtension();
            // Create a new PhotoMetadata record
            $docMetadata = new PenaltyDocument([
                'applied_record_id' => $id,
                'document_type' => $docType,
                'document_path' => $file_name,
                'document_name' => "Violation Video",
                'latitude' => $req->latitude ?? null,
                'longitude' => $req->longitude ?? null,
                'document_verified_by' => authUser()->id,
                'document_verified_at' => Carbon::now(),
            ]);
            $docMetadata->save();
            $data['video_details'] = $docMetadata;
        }
        if ($req->file('pdf')) {
            $docPath = $req->file('pdf')->move(public_path('FinePenalty/'), $req->pdf->getClientOriginalName());
            $file_name = 'FinePenalty/' . $req->pdf->getClientOriginalName();
            $docType = $req->pdf->getClientOriginalExtension();
            // Create a new PhotoMetadata record
            $docMetadata = new PenaltyDocument([
                'applied_record_id' => $id,
                'document_type' => $docType,
                'document_path' => $file_name,
                'document_name' => "Violation Document",
                'latitude' => $req->latitude ?? null,
                'longitude' => $req->longitude ?? null,
                'document_verified_by' => authUser()->id,
                'document_verified_at' => Carbon::now(),
            ]);
            $docMetadata->save();
            $data['pdf_details'] = $docMetadata;
        }
        return $data;
    }

    /**
     * | Get Uploaded Document
     */
    public function getDocument($id)
    {
        $docUrl = Config::get('constants.DOC_URL');
        $data = PenaltyDocument::select(
            'id',
            'latitude',
            'longitude',
            'document_name',
            DB::raw("concat('$docUrl/',document_path) as doc_path"),
        )
            ->where('applied_record_id', $id)
            ->where('status', 1)
            ->get();

        return $data;
    }
}
