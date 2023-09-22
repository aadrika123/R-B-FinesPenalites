<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenaltyFinalRecord extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function store($reqs)
    {
        return PenaltyFinalRecord::create($reqs);
    }
}
