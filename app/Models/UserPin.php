<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class UserPin extends Model
{
    protected $table = 'user_pins';
    protected $fillable = [
        'user_id',
        'pin'
    ];
}
