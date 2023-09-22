<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePassRequest;
use App\Http\Requests\Auth\OtpChangePass;
use App\Http\Requests\Auth\UserRegistrationRequest;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    // use Auth;
    private $_mUser;
    public function __construct()
    {
        $this->_mUser = new User();
    }
    
    /**
     * | Registration for users 
     */
    public function register(UserRegistrationRequest $req)
    {
        try {
            $data = $this->_mUser->insertData($req);
            return responseMsgs(true, "User Registration Done Successfully", $data, "API_1.01", "", responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "API_1.01", "", responseTime(), "POST", $req->deviceId ?? "");
        }
    }
     /**
     * | User Login
     */
    public function loginAuth(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            [
                'email' => 'required|email',
                'password' => 'required',
                'type' => "nullable|in:mobile"
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {
            // $mWfRoleusermap = new WfRoleusermap();
            $user = $this->_mUser->getUserByEmail($req->email);
            if (!$user)
                throw new Exception("Oops! Given email does not exist");
            if ($user->suspended == true)
                throw new Exception("You are not authorized to log in!");
            if (Hash::check($req->password, $user->password)) {
                $token = $user->createToken('my-app-token')->plainTextToken;
                // $menuRoleDetails = $mWfRoleusermap->getRoleDetailsByUserId($user->id);
                // if (empty(collect($menuRoleDetails)->first())) {
                //     throw new Exception('User has No Roles!');
                // }
                // $role = collect($menuRoleDetails)->map(function ($value, $key) {
                //     $values = $value['roles'];
                //     return $values;
                // });
                $data['token'] = $token;
                $data['userDetails'] = $user;
                // $data['userDetails']['role'] = $role;
                return responseMsgs(true, "You have Logged In Successfully", $data, 010101, "1.0", responseTime(), "POST", $req->deviceId);
            }
            throw new Exception("Password Not Matched");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | logout
     */
    public function logout(Request $req)
    {
        try {
            $req->user()->currentAccessToken()->delete();                               // Delete the Current Accessable Token
            return responseMsgs(true, "You have Logged Out", [], "", "1.0", responseTime(), "POST", $req->deviceId);
        } catch (Exception $e) {
            return response()->json($e, 400);
        }
    }

    /**
     * |
     */
    // Changing Password
    public function changePass(ChangePassRequest $request)
    {
        try {
            $id = auth()->user()->id;
            $user = User::find($id);
            $validPassword = Hash::check($request->password, $user->password);
            if ($validPassword) {
                $user->password = Hash::make($request->newPassword);
                $user->save();
                return responseMsgs(true, 'Successfully Changed the Password', "", "", "02", ".ms", "POST", $request->deviceId);
            }
            throw new Exception("Old Password dosen't Match!");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", "02", ".ms", "POST", $request->deviceId);
        }
    }

    /**
     * | Change Password by OTP 
     * | Api Used after the OTP Validation
     */
    public function changePasswordByOtp(OtpChangePass $request)
    {
        try {
            $id = auth()->user()->id;
            $user = User::find($id);
            $user->password = Hash::make($request->password);
            $user->save();
            return responseMsgs(true, 'Successfully Changed the Password', "", "", "02", ".ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", "01", ".ms", "POST", $request->deviceId);
        }
    }

    /**
     * | For Showing Logged In User Details 
     * | #user_id= Get the id of current user 
     * | if $redis available then get the value from redis key
     * | if $redis not available then get the value from sql database
     */
    public function myProfileDetails(Request $req)
    {
        try {
            $userId = auth()->user()->id;
            $mUser = new User();
            $details = $mUser->getUserById($userId);
            // return $details; die; 
            $usersDetails = [
                'id'        => $details->id,
                'NAME'      => $details->name,
                // 'USER_NAME' => $details->user_name,
                // 'mobile'    => $details->mobile,
                'email'     => $details->email,
                // 'ulb_id'    => $details->ulb_id,
                // 'ulb_name'  => $details->ulb_name,
            ];

            return responseMsgs(true, "Data Fetched", $usersDetails, 010101, "01", responseTime(), $req->getMethod(), "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", "01", ".ms", "POST", "");
        }
    }

    /**
     * |
     */

     public function edit(InfractionRecordingFormRequest $req)
    {
        try {
            $getData = PenaltyRecord::findOrFail($req->id);  // check the id is exists or not
            // $isExists = $this->_mInfracRecForms->checkExisting($req);  // check if existing
            // if ($isExists && $isExists->where('id', '!=', $req->id)->isNotEmpty())
            //     throw new Exception("Record Already Existing");

            $data = $this->_mInfracRecForms->edit($req, $getData);  // update record
            $metaReqs = [
                'full_name'                   => $req->fullName,
                'mobile'                      => $req->mobile,
                'email'                       => $req->email,
                'holding_no'                  => $req->holdingNo,
                'street_address'              => $req->streetAddress,
                'street_address_2'            => $req->streetAddress2,
                'city'                        => $req->city,
                'region'                      => $req->region,
                'postal_code'                 => $req->country,
                'country'                     => $req->country,
                'violation_id'                => $req->violationId,
                'violation_section_id'        => $req->violationSectionId,
                'penalty_amount'              => $req->penaltyAmount,
                'previous_violation_offence'  => $req->previousViolationOffence,
                'witness'                     => $req->witness,
                'witness_name'                => $req->witnessName,
                'witness_mobile'              => $req->witnessMobile,
                'penalty_previous_id'         => $req->id,
                'version_no'                  => $getData->version_no + 1,
                'updated_at'                  => Carbon::now()
            ];
            $data = DB::table('penalty_final_records')->insert($metaReqs);
            $queryTime = collect(DB::getQueryLog())->sum("time");
            return responseMsgsT(true, "Records Updated Successfully", $data, "3.2", $queryTime, responseTime(), "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "3.2", responseTime(), "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Model
     */
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
}
