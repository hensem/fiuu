<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    protected $table = 'applications';
    public $timestamps = false;

    protected $fillable = [
        'submission',
        'name',
        'created_by',
        'created_at',
        'status',
        'updated_by',
        'updated_at',
        'remark',
    ];

    /**
     * 🔹 Boot method
     * Protects applications from illegal edits and deletions.
     * Allows valid status transitions (1↔3 and 3→2).
     */
    protected static function boot()
    {
        parent::boot();

        // ✅ Prevent modification of submitted apps, but allow attach/detach transitions
        static::updating(function ($app) {
            $original = $app->getOriginal();
            $dirty = $app->getDirty();

            $oldStatus = (int)($original['status'] ?? 0);
            $newStatus = (int)($app->status ?? 0);

            // Fields being changed
            $changed = array_keys($dirty);

            // Always allowed technical fields
            $alwaysAllowed = ['status', 'submission', 'updated_by', 'updated_at'];

            // If we are only changing allowed system fields, skip validation
            if (empty(array_diff($changed, $alwaysAllowed))) {
                // ✅ permit attach (1→3), detach (3→1), or submit (3→2)
                if (
                    ($oldStatus === 1 && $newStatus === 3) ||
                    ($oldStatus === 3 && $newStatus === 1) ||
                    ($oldStatus === 3 && $newStatus === 2)
                ) {
                    return true;
                }
            }

            // ❌ Block all non-draft modifications (not status = 1)
            if ($oldStatus !== 1) {
                throw new \Exception('Only draft or attached applications can be modified.');
            }

            return true;
        });

        // 🚫 Prevent ALL deletions (no record removal allowed)
        static::deleting(function ($app) {
            throw new \Exception('Record deletion is not allowed in this system.');
        });
    }

    // ============================================================
    // 🔗 RELATIONSHIPS
    // ============================================================

    /**
     * 🔹 Application has many attachments.
     */
    public function attachments()
    {
        return $this->hasMany(ApplicationAttachment::class, 'application', 'id');
    }

    /**
     * 🔹 Application belongs to a submission.
     */
    public function submissionRel()
    {
        return $this->belongsTo(Submission::class, 'submission', 'id');
    }

    /**
     * 🔹 User who created this application.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 🔹 User who last updated this application.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ============================================================
    // ⚙️ HELPERS
    // ============================================================

    /** ✅ Check if application is editable (status = 1) */
    public function isEditable(): bool
    {
        return (int)$this->status === 1;
    }

    /** ✅ Check if application is attached (status = 3) */
    public function isAttached(): bool
    {
        return (int)$this->status === 3;
    }

    /** ✅ Check if application is submitted (status = 2) */
    public function isSubmitted(): bool
    {
        return (int)$this->status === 2;
    }

    /** ✅ Mark as attached (status = 3) */
    public function markAttached(): void
    {
        $this->status = 3;
        $this->save();
    }

    /** ✅ Mark as draft (status = 1) */
    public function markDraft(): void
    {
        $this->status = 1;
        $this->save();
    }
}
