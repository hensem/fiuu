<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    use HasFactory;

    protected $table = 'submissions';

    protected $fillable = [
        'user',
        'submitted_by',
        'submitted_at',
        'status',
        'partner',
        'created_at',
        'updated_at',
    ];

    public $timestamps = false;

    /**
     * 🚫 Prevent deletion or destruction of records (system-wide rule)
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($submission) {
            throw new \Exception('Record deletion is not allowed in this system.');
        });
    }

    // ============================================================
    // 🔗 RELATIONSHIPS
    // ============================================================

    /**
     * 🔹 Partner linked to this submission.
     */
    public function partnerRel()
    {
        return $this->belongsTo(Partner::class, 'partner', 'id');
    }

    /**
     * 🔹 Applications belonging to this submission.
     */
    public function applications()
    {
        return $this->hasMany(Application::class, 'submission', 'id');
    }

    /**
     * 🔹 User who created this submission.
     */
    public function userRelation()
    {
        return $this->belongsTo(User::class, 'user', 'id');
    }

    /**
     * 🔹 User who submitted this submission.
     */
    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by', 'id');
    }

    // ============================================================
    // ⚙️ HELPERS
    // ============================================================

    /**
     * ✅ Check if submission is still in draft status.
     */
    public function isDraft(): bool
    {
        return (int) $this->status === 1;
    }

    /**
     * ✅ Check if submission has been submitted.
     */
    public function isSubmitted(): bool
    {
        return (int) $this->status === 2;
    }

    /**
     * ✅ Check if submission is in “attached” intermediate state.
     */
    public function isAttached(): bool
    {
        return (int) $this->status === 3;
    }

    /**
     * 🔹 Helper: Returns partner info safely (for JSON or API).
     */
    public function getPartnerSummaryAttribute(): array
    {
        $partner = $this->partnerRel;
        return $partner ? [
            'id' => $partner->id,
            'name' => $partner->name,
        ] : [
            'id' => null,
            'name' => '(Deleted Partner)',
        ];
    }
}
