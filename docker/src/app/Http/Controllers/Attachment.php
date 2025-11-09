<?php
namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\ApplicationAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentController extends Controller
{
    public function store(Request $request, Application $application)
    {
        if (!$application->isDraft()) {
            return response()->json(['message' => 'Cannot upload to non-draft application.'], 422);
        }

        $validated = $request->validate([
            'file' => 'required|file|max:51200', // 50 MB
        ]);

        $file = $validated['file'];
        $disk = 'local';
        $path = $file->storeAs(
            'attachments/' . $application->id,
            Str::uuid() . '.' . $file->getClientOriginalExtension(),
            $disk
        );

        $attachment = ApplicationAttachment::create([
            'application_id' => $application->id,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'checksum' => hash_file('sha256', $file->getPathname()),
            'uploaded_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Attachment uploaded successfully.',
            'attachment' => $attachment,
        ]);
    }

    public function detach(Application $application, ApplicationAttachment $attachment)
    {
        if (!$application->isDraft()) {
            return response()->json(['message' => 'Cannot detach in non-draft application.'], 422);
        }

        if ($attachment->application_id !== $application->id) {
            return response()->json(['message' => 'Attachment not linked to this application.'], 404);
        }

        $attachment->delete(); // keep file as-is on disk

        return response()->json(['message' => 'Attachment detached successfully.']);
    }
}
?>