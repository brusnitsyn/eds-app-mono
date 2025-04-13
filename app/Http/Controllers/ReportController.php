<?php

namespace App\Http\Controllers;

use App\Exports\StaffExport;
use App\Models\Staff;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function reportExcel(Request $request)
    {
        $data = Staff::with(['certification'])->get();
        return Excel::download(new StaffExport($data), 'staff.xlsx');
    }
}
