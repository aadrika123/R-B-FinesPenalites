<?php

namespace App\IdGenerator;

use App\Models\IdGenerationParam;
use App\Models\Master\UlbMaster;
use Carbon\Carbon;

class IdGeneration
{
    protected $prefix;
    protected $paramId;
    protected $ulbId;
    protected $incrementStatus;

    public function __construct(int $paramId, int $ulbId)
    {
        $this->paramId = $paramId;
        $this->ulbId = $ulbId;
        $this->incrementStatus = true;
    }

    /**
     * | Id Generation Business Logic 
     */
    public function generate(): string
    {
        $todayDate = Carbon::now();
        $paramId = $this->paramId;
        $mIdGenerationParams = new IdGenerationParam();
        $mUlbMaster = new UlbMaster();
        $ulbDtls = $mUlbMaster::findOrFail($this->ulbId);
        $fYear = getFinancialYear($todayDate);
        $year = explode('-', $fYear);

        $yearsWithoutFirstTwoDigits = array_map(function ($year) {
            return substr($year, 2);
        }, $year);

        $y = implode($yearsWithoutFirstTwoDigits);

        $ulbDistrictCode = $ulbDtls->district_code;
        $ulbCategory = $ulbDtls->category;
        $code = $ulbDtls->code;

        $params = $mIdGenerationParams->getParams($paramId);
        $prefixString = $params->string_val;
        $stringVal =  $code .  $y . "00";  #_Type of Penalty Chalan Missing

        $stringSplit = collect(str_split($stringVal));
        $flag = ($stringSplit->sum()) % 9;
        $intVal = $params->int_val;
        // Case for the Increamental
        if ($this->incrementStatus == true) {
            $id = $stringVal . str_pad($intVal, 5, "0", STR_PAD_LEFT);
            $intVal += 1;
            $params->int_val = $intVal;
            $params->save();
        }

        // Case for not Increamental
        if ($this->incrementStatus == false) {
            $id = $stringVal  . str_pad($intVal, 5, "0", STR_PAD_LEFT);
        }

        return $prefixString . $id . $flag;
    }
}
