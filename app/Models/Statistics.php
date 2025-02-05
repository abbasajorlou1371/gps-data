<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Statistics extends Model
{
    protected $fillable = ['device_id', 'date', 'total_distance', 'stoppage_count', 'stoppage_duration', 'max_speed', 'average_speed'];

    protected function casts() {
        return [
            'date' => 'date',
            'total_distance' => 'integer',
            'stoppage_count' => 'integer',
            'stoppage_duration' => 'integer',
            'max_speed' => 'integer',
            'average_speed' => 'integer',
            'moving_duration' => 'integer',
        ];
    }

    protected $attributes = [
        'total_distance' => 0,
        'stoppage_count' => 0,
        'stoppage_duration' => 0,
        'max_speed' => 0,
        'average_speed' => 0,
        'moving_duration' => 0,
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
