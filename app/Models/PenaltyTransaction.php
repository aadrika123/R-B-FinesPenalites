<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenaltyTransaction extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function store($req)
    {
        return PenaltyTransaction::create($req);
    }
}
