<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\PublicController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Defines backend API endpoints for:
| - Authentication
| - Users
| - Partners
| - Applications
| - Submissions
| - Public access (no authentication)
|--------------------------------------------------------------------------
*/

// ======================
// 🔐 AUTH ROUTES (no token required)
// ======================
Route::post('/login', [AuthController::class, 'login']);

// ======================
// 🔒 PROTECTED ROUTES (require valid API token)
// ======================
Route::middleware('apiauth')->group(function () {

    // 👤 AUTH MANAGEMENT
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // 👥 USER MANAGEMENT
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);

    // 🤝 PARTNERS
    Route::get('/partners', [PartnerController::class, 'index']);       // list all
    Route::post('/partners', [PartnerController::class, 'store']);      // create
    Route::get('/partners/{id}', [PartnerController::class, 'show']);   // view
    Route::put('/partners/{id}', [PartnerController::class, 'update']); // edit

    // 📄 APPLICATIONS
    Route::get('/applications', [ApplicationController::class, 'index']);  // supports ?status=1 for draft
    Route::post('/applications', [ApplicationController::class, 'store']);
    Route::get('/applications/{id}', [ApplicationController::class, 'show']);
    Route::put('/applications/{id}', [ApplicationController::class, 'update']);

    // 📎 ATTACHMENTS (authenticated users)
    Route::post('/applications/{id}/attachments', [ApplicationController::class, 'addAttachment']);
    Route::delete('/applications/{id}/attachments/{attachmentId}', [ApplicationController::class, 'detachAttachment']);

    // 🔗 SIGNED ATTACHMENT DOWNLOAD (temporary URL, e.g. for internal preview)
    Route::get('/attachments/{id}/signed-download', [ApplicationController::class, 'signedDownload']);

    // 🧱 SUBMISSIONS
    Route::get('/submissions', [SubmissionController::class, 'index']);         // list all submissions
    Route::post('/submissions', [SubmissionController::class, 'store']);        // create new submission
    Route::get('/submissions/{id}', [SubmissionController::class, 'show']);     // view one submission
    Route::put('/submissions/{id}', [SubmissionController::class, 'update']);   // edit submission (partner / apps)
    Route::post('/submissions/{id}/submit', [SubmissionController::class, 'submit']); // finalize/submit
    Route::delete('/submissions/{id}', [SubmissionController::class, 'destroy']);     // delete
});

// ======================
// 🌍 PUBLIC ROUTES (no token required)
// ======================
Route::prefix('public')->group(function () {

    // 🧩 Partner can list all submissions by partner.code
    Route::get('/submissions/{code}', [PublicController::class, 'listByPartnerCode']);

    // ✅ Fixed: singular route for frontend match (/public/:code/submission/:submissionId)
    Route::get('/submission/{code}/{submissionId}', [PublicController::class, 'showSubmission']);

    // 📎 Direct attachment download for partner public access
    Route::get('/attachments/{attachmentId}/download', [PublicController::class, 'downloadAttachment']);
});

// ✅ PUBLIC SIGNED ATTACHMENT DOWNLOAD (short-lived, used by frontend)
// ⚠️ Must remain outside of any middleware group!
Route::get('/attachments/{id}/public-download', [ApplicationController::class, 'publicDownload'])
    ->name('attachment.signed');
