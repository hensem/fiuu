<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ApiToken extends Model
{
    use HasFactory;

    protected $table = 'api_tokens';

    protected $fillable = [
        'user_id',
        'token',
        'expires_at',
    ];

    // You can keep timestamps if your table has created_at / updated_at columns
    public $timestamps = true;

    /**
     * Relationship: each token belongs to a specific user.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Generate and store a new token for the given user.
     *
     * @param  int  $userId
     * @param  int  $hours
     * @return static
     */
    public static function generateForUser($userId, $hours = 24)
    {
        return self::create([
            'user_id'   => $userId,
            'token'     => Str::random(64),
            'expires_at'=> Carbon::now()->addHours($hours),
        ]);
    }

    /**
     * Validate a token string and return the associated user if valid.
     *
     * @param  string  $token
     * @return \App\Models\User|null
     */
    public static function validateToken($token)
    {
        $record = self::where('token', $token)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        return $record?->user;
    }

    /**
     * Invalidate (delete) a token by its string value.
     *
     * @param  string  $token
     * @return void
     */
    public static function invalidate($token)
    {
        self::where('token', $token)->delete();
    }
}
