<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $table = 'logs';
    public $timestamps = false;

    protected $fillable = [
        'controller','method','table','column','old_value','new_value','user','date_time'
    ];
}
