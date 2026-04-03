<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomInventory extends Model
{
    protected $table = 'room_inventory';

    public $timestamps = false;

    protected $fillable = [
        'room_type_id',
        'apply_date',
        'price',
        'available_allotment',
    ];

    public function roomType()
    {
        return $this->belongsTo(RoomType::class, 'room_type_id');
    }
}
