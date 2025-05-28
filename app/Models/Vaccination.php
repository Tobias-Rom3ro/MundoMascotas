<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vaccination extends Model
{
    use HasFactory;

    protected $fillable = [
        'pet_id',
        'vaccine_name',
        'application_date',
        'next_dose_date',
        'observations',
    ];

    protected $casts = [
        'application_date' => 'date',
        'next_dose_date' => 'date',
    ];

    public function pet()
    {
        return $this->belongsTo(Pet::class);
    }
}
