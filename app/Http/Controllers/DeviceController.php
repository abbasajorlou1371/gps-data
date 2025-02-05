<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Report;
use App\Models\Statistics;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function track(Request $request, $imei)
    {
        $date = $request->get('date', Carbon::today()->toDateString());

        $device = Device::where('imei', $imei)->firstOrFail();

        $reports = Report::where('device_id', $device->id)
            ->whereDate('date', $date)
            ->orderBy('time')
            ->get(['coordinates', 'speed', 'status', 'time']);

        $statistics = Statistics::where('device_id', $device->id)
            ->whereDate('date', $date)
            ->first();

        return response()->json([
            'device' => $device->only(['name', 'imei']),
            'path' => $reports->map(fn($report) => [
                'coordinates' => $report->coordinates,
                'speed' => $report->speed,
                'status' => $report->status,
                'time' => $report->time,
            ]),
            'statistics' => $statistics ? [
                'total_distance' => $statistics->total_distance,
                'stoppage_count' => $statistics->stoppage_count,
                'stoppage_duration' => $statistics->stoppage_duration,
                'moving_duration' => $statistics->moving_duration,
                'max_speed' => $statistics->max_speed,
            ] : null
        ]);
    }

    public function index()
    {
        return view('devices.track');
    }

    public function list()
    {
        $devices = Device::all(['name', 'imei']);
        return response()->json($devices);
    }

    public function latest($imei)
    {
        $device = Device::where('imei', $imei)->firstOrFail();

        $latestReport = Report::where('device_id', $device->id)->latest()->firstOrFail();

        $statistics = Statistics::where('device_id', $device->id)->latest()->first();

        return response()->json([
            'latest_point' => $latestReport->only(['coordinates', 'speed', 'status', 'time']),
            'statistics' => $statistics ? $statistics->only([
                'total_distance', 'stoppage_count', 'stoppage_duration', 'moving_duration', 'max_speed'
            ]) : null
        ]);
    }
}
