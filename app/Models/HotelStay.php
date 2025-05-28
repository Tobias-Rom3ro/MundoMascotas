<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotelStay extends Model
{
    use HasFactory;

    protected $fillable = [
        'pet_id',
        'client_id',
        'check_in_date',
        'check_out_date',
        'room_type',
        'special_requirements',
        'daily_rate',
        'total_cost',
        'status',
    ];

    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'daily_rate' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function pet()
    {
        return $this->belongsTo(Pet::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function getDurationAttribute()
    {
        return $this->check_in_date->diffInDays($this->check_out_date);
    }
}
