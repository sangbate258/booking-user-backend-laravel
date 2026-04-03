<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
    protected $table = 'hotels';

    public $timestamps = false;

    protected $fillable = [
        'partner_id',
        'name',
        'description',
        'address',
        'city',
        'star_rating',
        'status',
    ];

    public function roomTypes()
    {
        return $this->hasMany(RoomType::class, 'hotel_id');
    }
}