<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationAttachment extends Model
{
    protected $table = 'application_attachments';
    public $timestamps = false;

    protected $fillable = [
        'application',
        'attachment',
        'original_name',
        'mime',
        'size',
        'created_at',
    ];

    protected $casts = [
        'attachment' => 'string',
        'size' => 'float',
    ];

    protected $hidden = ['attachment']; // Hide raw BLOB in API responses

    /**
     * 🚫 Global protection: prevent record deletion
     * This enforces the "no delete" rule across all data.
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($attachment) {
            throw new \Exception('Record deletion is not allowed in this system.');
        });
    }

    // ============================================================
    // 🔗 RELATIONSHIPS
    // ============================================================

    /**
     * 🔹 Each attachment belongs to an application.
     */
    public function applicationRel()
    {
        return $this->belongsTo(Application::class, 'application', 'id');
    }

    // ============================================================
    // ⚙️ ACCESSORS & HELPERS
    // ============================================================

    /**
     * 🔹 Returns human-readable file size.
     */
    public function getReadableSizeAttribute(): string
    {
        if (!$this->size || $this->size <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = $this->size;
        $i = 0;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 2) . ' ' . $units[$i];
    }

    /**
     * 🔹 Ensures every attachment has a readable filename.
     */
    public function getOriginalNameAttribute($value): string
    {
        return $value ?: 'attachment-' . $this->id;
    }

    // ============================================================
    // 🔍 QUERY SCOPES
    // ============================================================

    /**
     * ⚡ Scope: Exclude the heavy BLOB column for lightweight API queries.
     *
     * Usage:
     *   ApplicationAttachment::withoutBlob()->get();
     */
    public function scopeWithoutBlob($query)
    {
        return $query->select([
            'id',
            'application',
            'original_name',
            'mime',
            'size',
            'created_at',
        ]);
    }
}
