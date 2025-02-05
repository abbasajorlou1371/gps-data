<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Casts\TimeCast;

class Report extends Model
{
    protected $fillable = [
        'device_id',
        'coordinates',
        'status',
        'speed',
        'date',
        'time'
    ];

    protected $casts = [
        'coordinates' => 'array',
        'status' => 'integer',
        'speed' => 'integer',
        'date' => 'date',
        'time' => TimeCast::class
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function isStopped()
    {
        return $this->status == 0 || $this->speed == 0;
    }

    public function isMoving()
    {
        return $this->status == 1 && $this->speed > 0;
    }
}
