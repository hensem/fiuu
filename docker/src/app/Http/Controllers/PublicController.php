<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\Submission;
use App\Models\ApplicationAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicController extends Controller
{
    /**
     * 🧾 List all submissions for a partner by their unique partner code.
     * Public endpoint: /api/public/submissions/{code}
     */
    public function listByPartnerCode($code)
    {
        $partner = Partner::where('code', $code)->firstOrFail();

        $subs = Submission::where('partner', $partner->id)
            ->whereIn('status', [1, 2]) // 1 = draft, 2 = submitted
            ->with(['partnerRel:id,name'])
            ->orderByDesc('id')
            ->get(['id', 'status', 'submitted_at', 'partner', 'created_at', 'updated_at']);

        // ✅ Ensure partnerRel always populated
        $subs->transform(function ($submission) use ($partner) {
            if (!$submission->partnerRel) {
                $submission->partnerRel = [
                    'id'   => $partner->id,
                    'name' => $partner->name,
                ];
            }
            return $submission;
        });

        return response()->json([
            'partner' => [
                'id'   => $partner->id,
                'name' => $partner->name,
                'code' => $partner->code,
            ],
            'submissions' => $subs,
        ]);
    }

    /**
     * 📄 Show one submission (with its applications and attachment metadata).
     * Public endpoint: /api/public/submissions/{code}/{submissionId}
     */
    public function showSubmission($code, $submissionId)
    {
        $partner = Partner::where('code', $code)->firstOrFail();

        $submission = Submission::where('id', $submissionId)
            ->where('partner', $partner->id)
            ->with([
                'applications' => function ($query) {
                    $query->select('id', 'name', 'status', 'remark', 'submission');
                },
                // ✅ Use the new scopeWithoutBlob for speed & safety
                'applications.attachments' => function ($query) {
                    $query->withoutBlob()
                        ->addSelect('original_name'); // Ensure filename is included
                },
            ])
            ->firstOrFail();

        // ✅ Ensure all attachments only contain metadata (no BLOB)
        $submission->applications->each(function ($app) {
            $app->attachments->transform(function ($att) {
                unset($att->attachment); // just in case
                return $att;
            });
        });

        // ✅ Always include partner info
        $submission->partnerRel = [
            'id'   => $partner->id,
            'name' => $partner->name,
            'code' => $partner->code,
        ];

        return response()->json([
            'partner'    => [
                'id'   => $partner->id,
                'name' => $partner->name,
                'code' => $partner->code,
            ],
            'submission' => $submission,
        ]);
    }

    /**
     * 📥 Download an attachment (public partner access).
     * Endpoint: /api/public/attachments/{attachmentId}/download?code=PARTNER_CODE
     */
    public function downloadAttachment(Request $request, $attachmentId)
    {
        $code = $request->query('code');
        if (!$code) {
            abort(400, 'Missing partner code');
        }

        $partner = Partner::where('code', $code)->firstOrFail();
        $att = ApplicationAttachment::findOrFail($attachmentId);

        // 🔒 Ensure attachment is linked to an application
        $applicationId = $att->application;
        if (!$applicationId) {
            abort(404, 'This attachment is not linked to any application');
        }

        // 🔒 Ensure the application belongs to a submission under this partner
        $submissionId = DB::table('applications')
            ->where('id', $applicationId)
            ->value('submission');

        if (!$submissionId) {
            abort(404, 'Application is not linked to any submission');
        }

        $belongs = DB::table('submissions')
            ->where('id', $submissionId)
            ->where('partner', $partner->id)
            ->exists();

        if (!$belongs) {
            abort(403, 'This partner does not have access to this attachment');
        }

        // ✅ Stream the file safely (no memory overload)
        return new StreamedResponse(function () use ($att) {
            echo $att->attachment;
        }, 200, [
            'Content-Type'        => $att->mime ?: 'application/octet-stream',
            'Content-Length'      => (string)($att->size ?? strlen($att->attachment)),
            'Content-Disposition' => 'attachment; filename="' . ($att->original_name ?? 'attachment-' . $att->id) . '"',
        ]);
    }
}
