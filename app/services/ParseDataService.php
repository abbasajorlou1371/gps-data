<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;

class ParseDataService
{
    public function parse(Request $request)
    {
        $data = $request->getContent();
        $data = json_decode(rtrim($data, "."), true);

        if (isset($data[0]['data'])) {
            // Multiple data entries
            $results = [];
            foreach ($data as $entry) {
                $results[] = $this->processData($entry['data']);
            }

            $results = collect($results)->sortBy('time')->values()->all();

            return $results;
        } else {
            // Single data entry
            return $this->processData($data['data']);
        }
    }

    private function processData($data)
    {
        throw_unless($this->checkDataFormat($data), \Exception::class, 'Invalid data format');

        $data = explode(',', substr($data, 11)); // Remove "+Hooshnic:V1.03," part

        $coordinates = [$data[1], $data[2]];
        $coordinates = $this->convertCoordinates($coordinates);
        $date = Carbon::createFromFormat('ymd', $data[4])->format('Y-m-d');
        $time = Carbon::createFromFormat('His', $data[5])->addHours(3)->addMinutes(30)->format('H:i:s');
        $speed = (int)$data[6];
        $status = (int)$data[8];
        $imei = $data[9];

        return [
            'coordinates' => $coordinates,
            'date' => $date,
            'time' => $time,
            'speed' => $speed,
            'status' => $status,
            'imei' => $imei,
        ];
    }

    private function convertCoordinates($coordinates)
    {
        // Convert latitude and longitude to float
        [$latitude, $longitude] = array_map('floatval', $coordinates);

        // Helper function to convert to decimal degrees
        $convertToDecimalDegrees = function ($coordinate) {
            $degrees = floor($coordinate / 100);
            $minutes = $coordinate - ($degrees * 100);
            return $degrees + ($minutes / 60);
        };

        // Convert latitude and longitude to decimal degrees
        $latitude = $convertToDecimalDegrees($latitude);
        $longitude = $convertToDecimalDegrees($longitude);

        return [$latitude, $longitude];
    }

    private function checkDataFormat($data)
    {
        $pattern = '/^\+Hooshnic:V\d+\.\d{2},\d{4}\.\d{5},\d{5}\.\d{4},\d{3},\d{6},\d{6},\d{3},\d{3},\d,\d{15}$/';

        return preg_match($pattern, $data);
    }
}
