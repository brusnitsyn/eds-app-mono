<?php

namespace App\Http\Controllers;

use App\Exports\StaffExport;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function reportExcel(Request $request)
    {
        $staff_ids = $request->query('staff_ids');
        $valid_type = $request->query('valid_type');
        $now = Carbon::now()->format('d.m.Y H-i');

        if (!empty($staff_ids)) {
            $data = Staff::whereIn('id', $staff_ids)
                ->with(['certification' => function($query) {
                $query->latest('created_at')->limit(1);
            }])->get();

            $fileName = "ЭРСП - Выбранный персонал от $now";

            return $this->generateFile($data, $fileName);
        }

        if (!empty($valid_type) && $valid_type == 'new-request') {
            $data = Staff::with(['certification' => function($query) {
                    $query->latest('created_at')->limit(1)
                        ->where('is_request_new', '=', true);
                }])
                ->get();

            $fileName = "ЭРСП - Требующие перевыпуск от $now";

            return $this->generateFile($data, $fileName);
        }

        if (!empty($valid_type) && $valid_type == 'no-valid') {
            $data = Staff::with(['certification' => function($query) {
                $query->latest('created_at')->limit(1)
                    ->where('is_request_new', '=', true);
            }])
                ->get();

            $fileName = "ЭРСП - Недействительные от $now";

            return $this->generateFile($data, $fileName);
        }

        $data = Staff::with(['certification' => function($query) {
                $query->latest('created_at')->limit(1);
            }])->get();

        $fileName = "ЭРСП - Персонал от $now";

        return $this->generateFile($data, $fileName);
    }

    private function generateFile($data, $fileName)
    {
        $fileName = $fileName ?? 'ЭРСП';
        return Excel::download(new StaffExport($data), "$fileName.xlsx");
    }
}
