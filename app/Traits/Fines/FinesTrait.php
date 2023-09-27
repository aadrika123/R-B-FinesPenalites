<?php

namespace App\Traits\Fines;

use Illuminate\Database\Eloquent\Collection;

trait FinesTrait
{
    /**
     * | Get Basic Details
     */
    public function generatePenaltyDetails($data)
    {
        return new Collection([
            ['displayString' => 'Violation Name', 'key' => 'violation_name', 'value' => $data->violation_name],
            ['displayString' => 'Violation Section', 'key' => 'violation_section', 'value' => $data->violation_section],
            ['displayString' => 'Penalty Amount', 'key' => 'penalty_amount', 'value' => $data->penalty_amount]
        ]);
    }

    /**
     * | Generating Bride Details
     */
    public function generateBasicDetails($data)
    {
        return new Collection([
            ['displayString' => 'Name', 'key' => 'name', 'value' => $data->full_name,],
            ['displayString' => 'Mobile', 'key' => 'mobile', 'value' => $data->mobile,],
            ['displayString' => 'Email', 'key' => 'email', 'value' => $data->email,],
            ['displayString' => 'Holding No.', 'key' => 'holding_no', 'value' => $data->holding_no,]
        ]);
    }

    /**
     * | Generating Groom Details
     */
    public function generateAddressDetails($data)
    {
        return new Collection([
            ['displayString' => 'Address', 'key' => 'street_address', 'value' => $data->street_address,],
            ['displayString' => 'Locality', 'key' => 'street_address2', 'value' => $data->street_address_2,],
            ['displayString' => 'City', 'key' => 'city', 'value' => $data->city,],
            ['displayString' => 'Region', 'key' => 'region', 'value' => $data->region,],
            ['displayString' => 'Postal Code', 'key' => 'postal_code', 'value' => $data->postal_code,],
        ]);
    }

    /**
     * | Witness Details
     */
    public function generateWitnessDetails($data)
    {
        return new Collection([
            ['displayString' => 'Witness Name', 'key' => 'witness_name', 'value' => $data->witness_name,],
            ['displayString' => 'Witness Mobile', 'key' => 'witness_mobile', 'value' => $data->witness_mobile,]
        ]);
    }

    /**
     * | Witness Details
     */
    // public function generateWitnessDetails($witnessDetails)
    // {
    //     return collect($witnessDetails)->map(function ($witnessDetail, $key) {
    //         return [
    //             $key + 1,
    //             $witnessDetail['withnessName'],
    //             $witnessDetail['withnessMobile'],
    //             $witnessDetail['withnessAddress'],
    //         ];
    //     });
    // }

    /**
     * | Generate Card Details
     */
    public function generateCardDtls($data)
    {

        $violationDtls = new Collection([
            ['displayString' => 'Name', 'key' => 'name', 'value' => $data->full_name,],
            ['displayString' => 'Mobile', 'key' => 'mobile', 'value' => $data->mobile,],
            ['displayString' => 'Violation Name', 'key' => 'violation_name', 'value' => $data->violation_name],
            ['displayString' => 'Penalty Amount', 'key' => 'penalty_amount', 'value' => $data->amount]
        ]);

        $cardElement = [
            'headerTitle' => "Violation Details",
            'data' => $violationDtls
        ];
        return $cardElement;
    }

    /**
     * | Save Request
     */
    public function makeRequest($request)
    {
        $reqs = [
            'bride_name'                   => $request->brideName,
            'bride_dob'                    => $request->brideDob,
            'bride_age'                    => $request->brideAge,
            'bride_nationality'            => $request->brideNationality,
            'bride_religion'               => $request->brideReligion,
            'bride_mobile'                 => $request->brideMobile,
            'bride_aadhar_no'              => $request->brideAadharNo,
            'bride_email'                  => $request->brideEmail,
            'bride_passport_no'            => $request->bridePassportNo,
            'bride_residential_address'    => $request->brideResidentialAddress,
            'bride_martial_status'         => $request->brideMartialStatus,
            'bride_father_name'            => $request->brideFatherName,
            'bride_father_aadhar_no'       => $request->brideFatherAadharNo,
            'bride_mother_name'            => $request->brideMotherName,
            'bride_mother_aadhar_no'       => $request->brideMotherAadharNo,
            'bride_guardian_name'          => $request->brideGuardianName,
            'bride_guardian_aadhar_no'     => $request->brideGuardianAadharNo,
            'groom_name'                   => $request->groomName,
            'groom_dob'                    => $request->groomDob,
            'groom_age'                    => $request->groomAge,
            'groom_aadhar_no'              => $request->groomAadharNo,
            'groom_nationality'            => $request->groomNationality,
            'groom_religion'               => $request->groomReligion,
            'groom_mobile'                 => $request->groomMobile,
            'groom_passport_no'            => $request->groomPassportNo,
            'groom_residential_address'    => $request->groomResidentialAddress,
            'groom_martial_status'         => $request->groomMartialStatus,
            'groom_father_name'            => $request->groomFatherName,
            'groom_father_aadhar_no'       => $request->groomFatherAadharNo,
            'groom_mother_name'            => $request->groomMotherName,
            'groom_mother_aadhar_no'       => $request->groomMotherAadharNo,
            'groom_guardian_name'          => $request->groomGuardianName,
            'groom_guardian_aadhar_no'     => $request->groomGuardianAadharNo,
            'marriage_date'                => $request->marriageDate,
            'marriage_place'               => $request->marriagePlace,
            'witness1_name'                => $request->witness1Name,
            'witness1_mobile_no'           => $request->witness1MobileNo,
            'witness1_residential_address' => $request->witness1ResidentialAddress,
            'witness2_name'                => $request->witness2Name,
            'witness2_residential_address' => $request->witness2ResidentialAddress,
            'witness2_mobile_no'           => $request->witness2MobileNo,
            'witness3_name'                => $request->witness3Name,
            'witness3_mobile_no'           => $request->witnessMobileNo,
            'witness3_residential_address' => $request->witness3ResidentialAddress,
            'appointment_date'             => $request->appointmentDate,
            'marriage_registration_date'   => $request->marriageRegistrationDate,
            'is_bpl'                       => $request->bpl,
            // 'registrar_id'                 => $request->registrarId,
            // 'user_id'                      => $request->userId,
            // 'citizen_id'                   => $request->citizenId,
            // 'application_no'               => $request->applicationNo,
            // 'initiator_role_id'            => $request->initiatorRoleId,
            // 'finisher_role_id'             => $request->finisherRoleId,
            // 'workflow_id'                  => $request->workflowId
        ];

        return $reqs;
    }
}
