<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\ApplicationAttachment;
use Illuminate\Http\Request;
use App\Http\Controllers\Traits\LogsChanges;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationController extends Controller
{
    use LogsChanges;

    /**
     * 📄 List all applications with optional filters
     * Query params:
     *   - status (int)
     *   - submission (int)
     */
    public function index(Request $request)
    {
        $query = Application::query()
            ->with([
                'attachments' => function ($q) {
                    $q->withoutBlob()->addSelect('original_name');
                },
                'createdBy:id,email',
                'updatedBy:id,email',
            ])
            ->orderByDesc('id');

        // Optional filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('submission')) {
            $query->where('submission', $request->submission);
        }

        $apps = $query->get()->map(function ($app) {
            return [
                'id' => $app->id,
                'submission' => $app->submission,
                'name' => $app->name,
                'status' => $app->status,
                'remark' => $app->remark,
                'created_by' => $app->created_by,
                'created_by_email' => $app->createdBy?->email,
                'created_at' => $app->created_at,
                'updated_by' => $app->updated_by,
                'updated_by_email' => $app->updatedBy?->email,
                'updated_at' => $app->updated_at,
                'attachments' => $app->attachments->map(function ($a) {
                    return [
                        'id' => $a->id,
                        'original_name' => $a->original_name,
                        'mime' => $a->mime,
                        'size' => $a->size,
                        'download_url' => URL::temporarySignedRoute(
                            'attachment.signed',
                            now()->addMinutes(5),
                            ['id' => $a->id]
                        ),
                    ];
                }),
            ];
        });

        return response()->json($apps);
    }

    /**
     * 📋 Get only draft applications (status = 1)
     * Used in dropdown selectors
     */
    public function getDrafts()
    {
        $apps = Application::where('status', 1)
            ->orderByDesc('id')
            ->get(['id', 'name']);

        return response()->json($apps);
    }

    /**
     * ✅ Ensure application is still draft before modifying
     */
    protected function ensureDraft(Application $app)
    {
        if ((int) $app->status !== 1) {
            abort(response()->json(['error' => 'Application is not in draft status.'], 422));
        }
    }

    /**
     * ➕ Create a new draft application
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'   => 'required|string|max:255',
            'remark' => 'nullable|string',
        ]);

        $app = new Application();
        $app->name = $request->name;
        $app->remark = $request->remark;
        $app->status = 1; // Draft
        $app->created_by = Auth::id();
        $app->updated_by = Auth::id();
        $app->created_at = Carbon::now();
        $app->updated_at = Carbon::now();
        $app->save();

        $this->logModelDiff('ApplicationController', 'store', null, $app);

        return response()->json($app, 201);
    }

    /**
     * 👁️ Show single application with lightweight attachment data
     */
    public function show($id)
    {
        $app = Application::with([
            'attachments' => function ($q) {
                $q->withoutBlob()->addSelect('original_name');
            },
            'createdBy:id,email',
            'updatedBy:id,email',
        ])->findOrFail($id);

        $data = $app->toArray();
        $data['created_by_email'] = $app->createdBy?->email;
        $data['updated_by_email'] = $app->updatedBy?->email;

        $data['attachments'] = $app->attachments->map(function ($a) {
            return [
                'id' => $a->id,
                'original_name' => $a->original_name,
                'mime' => $a->mime,
                'size' => $a->size,
                'download_url' => URL::temporarySignedRoute(
                    'attachment.signed',
                    now()->addMinutes(5),
                    ['id' => $a->id]
                ),
            ];
        });

        return response()->json($data);
    }

    /**
     * ✏️ Update an existing draft application
     */
    public function update(Request $request, $id)
    {
        $app = Application::findOrFail($id);
        $this->ensureDraft($app);
        $before = clone $app;

        $request->validate([
            'name'   => 'required|string|max:255',
            'remark' => 'nullable|string',
        ]);

        $app->fill($request->only(['name', 'remark']));
        $app->updated_by = Auth::id();
        $app->updated_at = Carbon::now();
        $app->save();

        $this->logModelDiff('ApplicationController', 'update', $before, $app);

        return response()->json($app);
    }

    /**
     * 📎 Add attachment (BLOB stored in DB)
     */
    public function addAttachment(Request $request, $id)
    {
        $app = Application::findOrFail($id);
        $this->ensureDraft($app);

        $request->validate([
            'file' => 'required|file',
        ]);

        $file = $request->file('file');
        $blob = file_get_contents($file->getRealPath());

        $att = new ApplicationAttachment();
        $att->application = $app->id;
        $att->attachment = $blob;
        $att->original_name = $file->getClientOriginalName();
        $att->mime = $file->getClientMimeType();
        $att->size = $file->getSize();
        $att->created_at = Carbon::now();
        $att->save();

        $this->logChange(
            'ApplicationController',
            'addAttachment',
            'application_attachments',
            'application',
            null,
            (string) $app->id
        );

        return response()->json([
            'id' => $att->id,
            'original_name' => $att->original_name,
            'mime' => $att->mime,
            'size' => $att->size,
            'download_url' => URL::temporarySignedRoute(
                'attachment.signed',
                now()->addMinutes(5),
                ['id' => $att->id]
            ),
        ], 201);
    }

    /**
     * 🗂️ Detach an attachment (no delete, keeps blob forever)
     */
    public function detachAttachment($id, $attachmentId)
    {
        $app = Application::findOrFail($id);
        $this->ensureDraft($app);

        $att = ApplicationAttachment::where('id', $attachmentId)
            ->where('application', $app->id)
            ->firstOrFail();

        $beforeAppId = $att->application;
        $att->application = null; // Unlink only
        $att->save();

        $this->logChange(
            'ApplicationController',
            'detachAttachment',
            'application_attachments',
            'application',
            (string) $beforeAppId,
            null
        );

        return response()->json(['message' => 'Attachment detached successfully (not deleted).']);
    }

    /**
     * 🔗 Generate short-lived signed URL (for internal authenticated use)
     */
    public function signedDownload($id)
    {
        $att = ApplicationAttachment::findOrFail($id);

        $url = URL::temporarySignedRoute(
            'attachment.signed',
            now()->addMinutes(5),
            ['id' => $att->id]
        );

        return response()->json(['url' => $url]);
    }

    /**
     * 📥 Public signed download handler (5-min link)
     */
    public function publicDownload(Request $request, $id)
    {
        if (!$request->hasValidSignature()) {
            abort(403, 'Invalid or expired download link.');
        }

        $att = ApplicationAttachment::findOrFail($id);

        return new StreamedResponse(function () use ($att) {
            echo $att->attachment;
        }, 200, [
            'Content-Type'        => $att->mime ?: 'application/octet-stream',
            'Content-Length'      => (string) ($att->size ?? strlen($att->attachment)),
            'Content-Disposition' => 'attachment; filename="' . ($att->original_name ?? 'attachment-' . $att->id) . '"',
        ]);
    }
}
