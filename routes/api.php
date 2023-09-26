<?php

use App\Http\Controllers\API\Master\CountryController;
use App\Http\Controllers\API\Master\UserTypeController;
use App\Http\Controllers\API\Master\ViolationController;
use App\Http\Controllers\API\Master\ViolationSectionController;
use App\Http\Controllers\Auth\UserController;
use App\Http\Controllers\Penalty\PenaltyRecordController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

/**
 * | Created On : 18-09-2023 
 * | Author : Umesh Kumar
 * | Code Status : Open 
 */
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/**
 * | User Register & Login
 | Controller No:01 */
Route::controller(UserController::class)->group(function () {
    Route::post('login', 'loginAuth'); //01
    Route::post('register', 'register'); //02
    Route::post('logout', 'logout')->middleware('auth:sanctum'); //03
});

Route::middleware('auth:sanctum')->group(function () {

    Route::controller(UserController::class)->group(function () {
        Route::post('change-password', 'changePass');                       // Change password with login
        Route::post('otp/change-password', 'changePasswordByOtp');           // Change Password With OTP   

        // User Profile APIs
        Route::get('my-profile-details', 'myProfileDetails');              // For get My profile Details
    });


    // ---------------------------------------------------------------- Master API Start ---------------------------------------------------------------
    Route::controller(CountryController::class)->group(function () {
        Route::post('country/retrieve-all', 'retrieveAll');
    });

    /**
     * | API Violation CRUD operation
         Controller No : 1
     */
    Route::controller(ViolationController::class)->group(function () {
        Route::post('violation/crud/save', 'createViolation');                                 // Save -------------------- 1.1
        Route::post('violation/crud/edit', 'updateViolation');                                 // Edit  ------------------- 1.2
        Route::post('violation/crud/get', 'ViolationById');                                    // Get By Id --------------- 1.3
        Route::post('violation/crud/list', 'getViolation');                                    // Get All ----------------- 1.4
        Route::post('violation/crud/delete', 'deleteViolation');                               // Delete ------------------ 1.5
    });



    Route::controller(UserTypeController::class)->group(function () {
        Route::post('user-type/retrieve-all', 'retrieveAll');                           // Get all                  M_API_7.1
        Route::post('user-type/active-all', 'activeAll');                               // Get all active           M_API_7.2
        Route::post('user-type/store', 'store');                                        // store                    M_API_7.3
        Route::post('user-type/edit', 'edit');                                          // edit                     M_API_7.4
        Route::post('user-type/show', 'show');                                          // show                     M_API_7.5
        Route::post('user-type/delete', 'delete');                                      // delete                   M_API_7.6
        Route::post('user-type/search', 'search');                                      // search                   M_API_7.7
    });


    // ---------------------------------------------------------------- Master API End ---------------------------------------------------------------
    /**
     * | API Infraction Recording Form  
         Controller No : 3
     */
    Route::controller(PenaltyRecordController::class)->group(function () {
        Route::post('penalty-record/crud/save', 'store');                                      // Save ---------------- 3.1
        Route::post('penalty-record/crud/show', 'show');                                       // Get By Id ----------- 3.3
        Route::post('penalty-record/crud/active-all', 'activeAll');                            // Get Active All ------ 3.5
        Route::post('penalty-record/crud/delete', 'delete');                                   // Delete -------------- 3.6
        Route::post('penalty-record/crud/search', 'searchByApplicationNo');                    // search -------------- 3.6

        Route::post('penalty-record/get-uploaded-document', 'getUploadedDocuments');
        Route::post('penalty-record/inbox', 'inbox');
        Route::post('penalty-record/detail', 'penaltyDetails');
        Route::post('penalty-record/approve', 'approvePenalty');
        Route::post('penalty-record/recent-applications', 'recentApplications');
        Route::post('penalty-record/recent-challans', 'recentChallans');
        Route::post('penalty-record/challan-search', 'searchChallan');
        Route::post('penalty-record/get-challan', 'challanDetails');
        Route::post('penalty-record/offline-challan-payment', 'challanPayment');
        Route::post('penalty-record/payment-receipt', 'paymentReceipt');
        Route::post('penalty-record/on-spot-challan', 'onSpotChallan');
        Route::post('report/violation-wise', 'violationData');
    });

    /**
     * | API Violation Section CRUD operation
         Controller No : 4
     */
    Route::controller(ViolationSectionController::class)->group(function () {
        Route::post('violation-section/crud/save', 'createViolationSection');                                 // Save -------------------- 1.1
        Route::post('violation-section/crud/edit', 'updateViolationSection');                                 // Edit  ------------------- 1.2
        Route::post('violation-section/crud/get', 'getSectionById');                                    // Get By Id --------------- 1.3
        Route::post('violation-section/crud/list', 'getSectionList');                                    // Get All ----------------- 1.4
        Route::post('violation-section/crud/delete', 'deleteSection');                               // Delete ------------------ 1.5
    });
});
