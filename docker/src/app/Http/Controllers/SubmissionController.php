<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use App\Models\Application;
use Illuminate\Http\Request;
use App\Http\Controllers\Traits\LogsChanges;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SubmissionController extends Controller
{
    use LogsChanges;

    /**
     * 📋 List all submissions with partner + applications (no blobs)
     */
    public function index()
    {
        $submissions = Submission::with([
            'partnerRel:id,name',
            'applications' => function ($q) {
                $q->select('id', 'name', 'status', 'remark', 'submission');
            },
            'applications.attachments' => function ($q) {
                $q->withoutBlob()->addSelect('original_name');
            },
        ])
            ->orderByDesc('id')
            ->get();

        // Normalize missing partner for frontend
        $submissions->transform(function ($s) {
            $s->partnerRel = $s->partnerRel ?: ['id' => null, 'name' => '(Deleted Partner)'];
            return $s;
        });

        return response()->json($submissions);
    }

    /**
     * ➕ Create a new submission (default: Draft)
     */
    public function store(Request $request)
    {
        $request->validate([
            'partner' => 'required|integer|exists:partners,id',
        ]);

        $submission = new Submission();
        $submission->user = Auth::id();
        $submission->partner = $request->partner;
        $submission->status = 1; // Draft
        $submission->submitted_by = null;
        $submission->submitted_at = null;
        $submission->created_at = Carbon::now();
        $submission->updated_at = Carbon::now();
        $submission->save();

        $this->logModelDiff('SubmissionController', 'store', null, $submission);

        $submission->load('partnerRel:id,name');
        $submission->partnerRel = $submission->partnerRel ?: ['id' => null, 'name' => '(Deleted Partner)'];

        return response()->json([
            'message' => 'Submission created successfully',
            'submission' => $submission,
        ], 201);
    }

    /**
     * 👁️ Show one submission (with apps + attachments)
     */
    public function show($id)
    {
        $submission = Submission::with([
            'partnerRel:id,name',
            'applications' => function ($q) {
                $q->select('id', 'name', 'status', 'remark', 'submission');
            },
            'applications.attachments' => function ($q) {
                $q->withoutBlob()->addSelect('original_name');
            },
        ])->findOrFail($id);

        $submission->partnerRel = $submission->partnerRel ?: ['id' => null, 'name' => '(Deleted Partner)'];

        return response()->json($submission);
    }

    /**
     * ✏️ Update submission (change partner, attach/detach applications)
     */
    public function update(Request $request, $id)
    {
        $submission = Submission::findOrFail($id);

        // Only drafts are editable
        if ($submission->status !== 1) {
            return response()->json(['error' => 'Cannot modify a submitted submission.'], 403);
        }

        $before = clone $submission;

        $request->validate([
            'partner' => 'nullable|integer|exists:partners,id',
            'add_application_ids' => 'array',
            'remove_application_ids' => 'array',
        ]);

        DB::beginTransaction();
        try {
            // 🔹 Update partner if provided
            if ($request->filled('partner')) {
                $submission->partner = $request->partner;
            }

            // 🔹 Attach applications (only if status = 1)
            if ($request->has('add_application_ids') && !empty($request->add_application_ids)) {
                $draftApps = Application::whereIn('id', $request->add_application_ids)
                    ->where('status', 1)
                    ->get();

                if ($draftApps->count() !== count($request->add_application_ids)) {
                    DB::rollBack();
                    return response()->json([
                        'error' => 'Some applications are not in draft status (status ≠ 1) and cannot be attached.',
                    ], 422);
                }

                foreach ($draftApps as $app) {
                    $app->submission = $submission->id;
                    $app->status = 3; // Attached
                    $app->updated_by = Auth::id();
                    $app->updated_at = Carbon::now();
                    $app->save();
                }
            }

            // 🔹 Detach applications (only if status = 3)
            if ($request->has('remove_application_ids') && !empty($request->remove_application_ids)) {
                $attachedApps = Application::whereIn('id', $request->remove_application_ids)
                    ->where('submission', $submission->id)
                    ->where('status', 3)
                    ->get();

                if ($attachedApps->isEmpty()) {
                    DB::rollBack();
                    return response()->json([
                        'error' => 'No valid attached applications found to detach (must have status = 3).',
                    ], 422);
                }

                foreach ($attachedApps as $app) {
                    $app->submission = null;
                    $app->status = 1; // back to Draft
                    $app->updated_by = Auth::id();
                    $app->updated_at = Carbon::now();
                    $app->save();
                }
            }

            $submission->updated_at = Carbon::now();
            $submission->save();

            DB::commit();

            $this->logModelDiff('SubmissionController', 'update', $before, $submission);

            // Reload latest data
            $submission->load([
                'partnerRel:id,name',
                'applications' => function ($q) {
                    $q->select('id', 'name', 'status', 'remark', 'submission');
                },
                'applications.attachments' => function ($q) {
                    $q->withoutBlob()->addSelect('original_name');
                },
            ]);
            $submission->partnerRel = $submission->partnerRel ?: ['id' => null, 'name' => '(Deleted Partner)'];

            return response()->json([
                'message' => 'Submission updated successfully',
                'submission' => $submission,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Update failed',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 🚀 Submit the submission (status 1 → 2)
     * Also mark attached applications as submitted (status 2)
     */
    public function submit($id)
    {
        $submission = Submission::with('applications')->findOrFail($id);

        if ($submission->status !== 1) {
            return response()->json(['error' => 'Submission already submitted or locked.'], 400);
        }

        if ($submission->applications->isEmpty()) {
            return response()->json(['error' => 'Cannot submit an empty submission.'], 422);
        }

        $before = clone $submission;

        DB::beginTransaction();
        try {
            $submission->status = 2; // Submitted
            $submission->submitted_by = Auth::id();
            $submission->submitted_at = Carbon::now();
            $submission->updated_at = Carbon::now();
            $submission->save();

            Application::where('submission', $submission->id)
                ->update([
                    'status' => 2,
                    'updated_by' => Auth::id(),
                    'updated_at' => Carbon::now(),
                ]);

            DB::commit();

            $this->logModelDiff('SubmissionController', 'submit', $before, $submission);

            $submission->load([
                'partnerRel:id,name',
                'applications' => function ($q) {
                    $q->select('id', 'name', 'status', 'remark', 'submission');
                },
                'applications.attachments' => function ($q) {
                    $q->withoutBlob()->addSelect('original_name');
                },
            ]);
            $submission->partnerRel = $submission->partnerRel ?: ['id' => null, 'name' => '(Deleted Partner)'];

            return response()->json([
                'message' => 'Submission submitted successfully',
                'submission' => $submission,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Submit failed',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 🚫 No actual deletion — only detach related applications safely
     */
    public function destroy($id)
    {
        $submission = Submission::findOrFail($id);

        // Only drafts can be reset
        if ($submission->status !== 1) {
            return response()->json(['error' => 'Cannot remove or reset a submitted submission.'], 403);
        }

        DB::transaction(function () use ($submission) {
            Application::where('submission', $submission->id)
                ->update([
                    'submission' => null,
                    'status' => 1, // revert to draft
                    'updated_by' => Auth::id(),
                    'updated_at' => Carbon::now(),
                ]);

            // Log the reset (but don't delete)
            $this->logModelDiff('SubmissionController', 'destroy', $submission, $submission);
        });

        return response()->json(['message' => 'Submission detached from all applications (not deleted).']);
    }
}
