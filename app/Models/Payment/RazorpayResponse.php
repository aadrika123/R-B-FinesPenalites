<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RazorpayResponse extends Model
{
    use HasFactory;
    protected $guarded = [];

    /**
     * | Create 
     */
    public function store($req)
    {
        return RazorpayResponse::create($req);
    }
}
