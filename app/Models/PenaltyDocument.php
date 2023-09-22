<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenaltyDocument extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function storeDocument($req, $id, $applicationNo)
    {
        if ($req->file('photo')) {
            $docPath = $req->file('photo')->move(public_path('FinePenalty/Documents/' . $applicationNo), $req->photo->getClientOriginalName());
            $file_name = 'FinePenalty/Documents/' . $applicationNo . '/' . $req->photo->getClientOriginalName();
            $docType = $req->photo->getClientOriginalExtension();
            // Create a new PhotoMetadata record
            $docMetadata = new PenaltyDocument([
                'irf_id' => $id,
                'document_type' => $docType,
                'document_path' => $file_name,
                'latitude' => $req->latitude,
                'longitude' => $req->longitude,
                'document_verified_by' => authUser()->id,
                'document_verified_at' => Carbon::now(),
            ]);
            $docMetadata->save();
            $data['photo_details'] = $docMetadata;
        }

        if ($req->file('audioVideo')) {
            $docPath = $req->file('audioVideo')->move(public_path('FinePenalty/Documents/' . $applicationNo), $req->audioVideo->getClientOriginalName());
            $file_name = 'FinePenalty/Documents/' . $applicationNo . '/' . $req->audioVideo->getClientOriginalName();
            $docType = $req->audioVideo->getClientOriginalExtension();
            // Create a new PhotoMetadata record
            $docMetadata = new PenaltyDocument([
                'irf_id' => $id,
                'document_type' => $docType,
                'document_path' => $file_name,
                'latitude' => $req->latitude ?? null,
                'longitude' => $req->longitude ?? null,
                'document_verified_by' => authUser()->id,
                'document_verified_at' => Carbon::now(),
            ]);
            $docMetadata->save();
            $data['video_details'] = $docMetadata;
        }
        if ($req->file('pdf')) {
            $docPath = $req->file('pdf')->move(public_path('FinePenalty/Documents/' . $applicationNo), $req->pdf->getClientOriginalName());
            $file_name = 'FinePenalty/Documents/' . $applicationNo . '/' . $req->pdf->getClientOriginalName();
            $docType = $req->pdf->getClientOriginalExtension();
            // Create a new PhotoMetadata record
            $docMetadata = new PenaltyDocument([
                'irf_id' => $id,
                'document_type' => $docType,
                'document_path' => $file_name,
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
}
