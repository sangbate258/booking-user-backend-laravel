<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomType extends Model
{
    protected $table = 'room_types';

    public $timestamps = false;

    protected $fillable = [
        'hotel_id',
        'name',
        'slug',
        'base_price',
        'max_adults',
        'max_children',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class, 'hotel_id');
    }
    public function inventories()
    {
        return $this->hasMany(RoomInventory::class, 'room_type_id');
    }
}
