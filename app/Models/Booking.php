<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $table = 'bookings';

    public $timestamps = false;

    protected $fillable = [
        'booking_code',
        'user_id',
        'hotel_id',
        'promotion_id',
        'guest_name',
        'guest_phone',
        'total_amount',
        'platform_fee',
        'status',
        'created_at',
    ];

    public function details()
    {
        return $this->hasMany(BookingDetail::class, 'booking_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class, 'hotel_id');
    }
}
