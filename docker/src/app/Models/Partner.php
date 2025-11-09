<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    protected $table = 'partners';
    public $timestamps = false;

    protected $fillable = [
        'name','email','contact_person','phone','address','status',
        'created_by','created_at','updated_by','updated_at','code'
    ];
}
