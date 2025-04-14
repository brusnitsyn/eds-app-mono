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
        $data = Staff::with(['certification' => function($query) {
            $query->latest('created_at')->limit(1);
        }])->get();

        return Excel::download(new StaffExport($data), 'staff.xlsx');
    }
}
