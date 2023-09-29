<?php

use App\Http\Controllers\API\Master\CountryController;
use App\Http\Controllers\API\Master\DepartmentController;
use App\Http\Controllers\API\Master\SectionController;
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
     * | User Login 
         Controller No : 1
     */
Route::controller(UserController::class)->group(function () {
    Route::post('login', 'loginAuth');                                                    // Login -------------------- 0101
    Route::post('register', 'register');                                                  // Register ----------------- 0102
    Route::post('logout', 'logout')->middleware('auth:sanctum');                          // Logout ------------------- 0103
});

Route::middleware('auth:sanctum')->group(function () {

    /**
     * | 
         Controller No : 1
     */
    Route::controller(UserController::class)->group(function () {
        Route::post('change-password', 'changePass');                                        // Change Password --------- 0103
        Route::post('otp/change-password', 'changePasswordByOtp');                           // Forget Password --------- 0104

        // User Profile APIs
        Route::get('my-profile-details', 'myProfileDetails');                                // Profile ----------------- 0105
    });

    /**
     * | API Department CRUD operation
         Controller No : 2
     */
    Route::controller(DepartmentController::class)->group(function () {
        Route::post('department/crud/save', 'createDepartment');                              // Save -------------------- 0201
        Route::post('department/crud/edit', 'updateDepartment');                              // Edit  ------------------- 0202
        Route::post('department/crud/get', 'getDepartmentById');                              // Get By Id --------------- 0203
        Route::post('department/crud/list', 'getDepartmentList');                             // Get All ----------------- 0204
        Route::post('department/crud/delete', 'deleteDepartment');                            // Delete ------------------ 0205
        Route::post('department/list', 'getDepartmentList');                                  // Get All ----------------- 0206

    });

    /**
     * | API Section CRUD operation
         Controller No : 3
     */
    Route::controller(SectionController::class)->group(function () {
        Route::post('section/crud/save', 'createSection');                                   // Save -------------------- 0301
        Route::post('section/crud/edit', 'updateSection');                                   // Edit  ------------------- 0302
        Route::post('section/crud/get', 'getSectionById');                                   // Get By Id --------------- 0303
        Route::post('section/crud/list', 'getSectionList');                                  // Get All ----------------- 0304
        Route::post('section/crud/delete', 'deleteSection');                                 // Delete ------------------ 0305
        Route::post('section/list', 'getSectionListById');                                   // Get All ----------------- 0306

    });

    /**
     * | API Violation CRUD operation
         Controller No : 4
     */
    Route::controller(ViolationController::class)->group(function () {
        Route::post('violation/crud/save', 'createViolation');                               // Save -------------------- 0401
        Route::post('violation/crud/edit', 'updateViolation');                               // Edit  ------------------- 0402
        Route::post('violation/crud/get', 'ViolationById');                                  // Get By Id --------------- 0403
        Route::post('violation/crud/list', 'getViolation');                                  // Get All ----------------- 0404
        Route::post('violation/crud/delete', 'deleteViolation');                             // Delete ------------------ 0405
        Route::post('violation/list', 'getViolationListBySectionId');                        // Get All ----------------- 0406

    });

        /**
     * | API Violation Section CRUD operation
         Controller No : 5
     */
    Route::controller(ViolationSectionController::class)->group(function () {
        Route::post('violation-section/crud/save', 'createViolationSection');                // Save -------------------- 0501
        Route::post('violation-section/crud/edit', 'updateViolationSection');                // Edit  ------------------- 0502
        Route::post('violation-section/crud/get', 'getSectionById');                         // Get By Id --------------- 0503
        Route::post('violation-section/crud/list', 'getSectionList');                        // Get All ----------------- 0504
        Route::post('violation-section/crud/delete', 'deleteSection');                       // Delete ------------------ 0505
     
        Route::post('user-list', 'getUserList');                                             // Get All ----------------- 0506
        Route::post('challan-category/list', 'getCategoryList');                             // Get All ----------------- 0507


    });


    // ---------------------------------------------------------------- Master API End ---------------------------------------------------------------
    /**
     * | API Penalty Record Application Form  
         Controller No : 6
     */
    Route::controller(PenaltyRecordController::class)->group(function () {
        Route::post('penalty-record/crud/save', 'store');                                      // Save ---------------- 0601
        Route::post('penalty-record/crud/show', 'show');                                       // Get By Id ----------- 0602
        Route::post('penalty-record/crud/active-all', 'activeAll');                            // Get Active All ------ 0603
        Route::post('penalty-record/crud/delete', 'delete');                                   // Delete -------------- 0604
        Route::post('penalty-record/crud/search', 'searchByApplicationNo');                    // search -------------- 0605

        Route::post('penalty-record/get-uploaded-document', 'getUploadedDocuments');       // get uploaded documents ---------- 0606
        Route::post('penalty-record/inbox', 'inbox');                                      // inbox details ------------------- 0607
        Route::post('penalty-record/detail', 'penaltyDetails');                            // penalty details ----------------- 0608
        Route::post('penalty-record/approve', 'approvePenalty');                           // penalty approval ---------------- 0609
        Route::post('penalty-record/recent-applications', 'recentApplications');           // get recent applications --------- 0610
        Route::post('penalty-record/recent-challans', 'recentChallans');                   // get recent challans ------------- 0611
        Route::post('penalty-record/challan-search', 'searchChallan');                     // get search challans ------------- 0612
        Route::post('penalty-record/get-challan', 'challanDetails');                       // get challans details ------------ 0613
        Route::post('penalty-record/offline-challan-payment', 'challanPayment');           // get payment details ------------- 0614
        Route::post('penalty-record/payment-receipt', 'paymentReceipt');                   // get payment receipt details ----- 0615
        Route::post('penalty-record/on-spot-challan', 'onSpotChallan');                    // get on-spot challans details ---- 0616
        Route::post('report/violation-wise', 'violationData');                             // get violations data ------------- 0617
        Route::post('report/challan-wise', 'challanData');                                 // get challenges data ------------- 0618
        Route::post('report/collection-wise', 'collectionData');                           // get collection data ------------- 0619
    });
});
