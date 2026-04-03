<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class User extends Model
{
    use HasApiTokens, Notifiable;

    protected $table = 'users';

    public $timestamps = false;

    protected $fillable = [
        'full_name',
        'email',
        'password_hash',
        'phone',
        'is_active',
    ];

    protected $hidden = [
        'password_hash',
    ];
}