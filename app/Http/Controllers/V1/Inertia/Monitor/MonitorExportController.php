<?php

namespace App\Http\Controllers\V1\Inertia\Monitor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MentorHoursExport;
use App\Models\Roles\Monitor\User\Monitor; // 👈 use the correct Monitor model

class MonitorExportController extends Controller
{
    public function exportExcel(Monitor $monitor)
    {
        $fileName = 'mentor_' . $monitor->id . '_hours.xlsx';
        return Excel::download(new MentorHoursExport($monitor), $fileName);
    }
}
