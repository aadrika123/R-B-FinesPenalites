<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenaltyTransaction extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $hidden = ['created_at', 'updated_at'];

    public function store($req)
    {
        return PenaltyTransaction::create($req);
    }

    /**
     * | Transaction Details
     */
    public function tranDtl()
    {
        return PenaltyTransaction::select(
            'penalty_transactions.id',
            'tran_no',
            'tran_date',
            'payment_mode',
            'penalty_transactions.amount',
            'penalty_transactions.penalty_amount',
            'penalty_transactions.total_amount',
            'application_no',
            'full_name',
            'challan_no',
            'challan_date',
            'violations.violation_name',
            'departments.department_name as department',

        )
            ->join('penalty_final_records', 'penalty_final_records.id', 'penalty_transactions.application_id')
            ->join('penalty_challans', 'penalty_challans.id', 'penalty_transactions.challan_id')
            ->join('violations', 'violations.id', 'penalty_final_records.violation_id')
            ->join('departments', 'departments.id', 'violations.department_id');
    }
}
