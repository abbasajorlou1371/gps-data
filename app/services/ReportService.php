<?php

namespace App\Services;

use App\Models\Report;
use App\Models\Statistics;
use Carbon\Carbon;

class ReportService
{
    public function processReports(array $reports, $device)
    {
        $statistics = Statistics::firstOrCreate(
            ['device_id' => $device->id, 'date' => Carbon::today()->toDateString()],
        );

        $previousReport = Report::where('device_id', $device->id)->latest()->first();
        $wasStoppedPreviously = false;

        foreach ($reports as $report) {
            $isStopped = $report->isStopped();

            // Only save the report if it's moving or if it's the first stopped report
            if (!$isStopped || !$wasStoppedPreviously) {
                $report->save();

                if ($isStopped) {
                    $statistics->stoppage_count++;
                }
            }

            if ($previousReport) {
                $distance = $this->calculateDistance($previousReport->coordinates, $report->coordinates);
                $statistics->total_distance += $distance;
            }

            $statistics->max_speed = max($statistics->max_speed, $report->speed);
            $previousReport = $report;
            $wasStoppedPreviously = $isStopped;
        }

        $this->updateStatistics($statistics, collect($reports));
        $statistics->save();
    }

    private function updateStatistics(Statistics $statistics, $reports)
    {
        $stoppedReports = $reports->filter->isStopped();
        $movingReports = $reports->filter->isMoving();

        $statistics->stoppage_count = $this->calculateStoppageCount($stoppedReports);
        $statistics->stoppage_duration = $this->calculateDuration($stoppedReports);
        $statistics->moving_duration = $this->calculateDuration($movingReports);
    }

    private function calculateStoppageCount($reports)
    {
        if ($reports->isEmpty()) {
            return 0;
        }

        $count = 0;
        $lastState = false; // Start assuming moving

        foreach ($reports->sortBy('time') as $report) {
            $currentState = $report->isStopped();

            // Count when transitioning from moving to stopped
            if (!$lastState && $currentState) {
                $count++;
            }

            $lastState = $currentState;
        }

        return $count;
    }

    private function calculateDuration($reports)
    {
        if ($reports->isEmpty()) {
            return 0;
        }

        $duration = 0;
        $reports = $reports->sortBy('time');
        $startTime = null;
        $previousTime = null;
        $lastState = null;

        foreach ($reports as $report) {
            $currentTime = Carbon::parse($report->time);
            $currentState = $report->isStopped();

            if ($previousTime) {
                // Only count duration if we're in the same state
                if ($lastState === $currentState) {
                    $duration += $previousTime->diffInSeconds($currentTime);
                } else {
                    // Reset start time when state changes
                    $startTime = $currentTime;
                }
            } else {
                $startTime = $currentTime;
            }

            $previousTime = $currentTime;
            $lastState = $currentState;
        }

        return $duration;
    }

    private function calculateDistance($coordinates1, $coordinates2)
    {
        [$lat1, $lon1] = $coordinates1;
        [$lat2, $lon2] = $coordinates2;

        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
