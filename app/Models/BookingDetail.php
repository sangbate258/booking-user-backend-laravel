<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingDetail extends Model
{
    protected $table = 'booking_details';

    public $timestamps = false;

    protected $fillable = [
        'booking_id',
        'room_type_id',
        'check_in_date',
        'check_out_date',
        'rooms_count',
        'subtotal',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function roomType()
    {
        return $this->belongsTo(RoomType::class, 'room_type_id');
    }
}