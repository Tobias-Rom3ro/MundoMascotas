<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'segment',
        'description',
    ];

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function scopeBySegment($query, $segment)
    {
        return $query->where('segment', $segment);
    }
}
