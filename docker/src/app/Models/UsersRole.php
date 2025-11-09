<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsersRole extends Model
{
    protected $table = 'users_role';
    public $timestamps = false;

    protected $fillable = ['role', 'created_by', 'created_on'];
}
