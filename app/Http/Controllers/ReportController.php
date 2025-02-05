<?php

namespace App\Http\Controllers;

use App\Services\ParseDataService;
use App\Services\ReportService;
use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\Report;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    public function __construct(
        protected ParseDataService $parseDataService,
        protected ReportService $reportService
    ) {}

    public function store(Request $request)
    {
        try {
            $parsedData = $this->parseDataService->parse($request);

            if (empty($parsedData)) {
                throw new \Exception('No data to process');
            }

            $device = Device::where('imei', $parsedData[0]['imei'])->firstOrFail();

            $reports = [];
            foreach ($parsedData as $data) {
                unset($data['imei']); // Remove imei as it's not in fillable
                $report = new Report($data);
                $report->device_id = $device->id;
                $reports[] = $report;
            }

            $this->reportService->processReports($reports, $device);

        } catch (\Exception $e) {
            Log::error('GPS Report Processing Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json(['message' => 'Data processed successfully']);
    }
}
