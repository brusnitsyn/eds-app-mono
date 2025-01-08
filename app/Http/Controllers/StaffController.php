<?php

namespace App\Http\Controllers;

use App\Actions\Eds\ReadCertificate;
use App\Http\Requests\Staff\CreateStaffRequest;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $query = Staff::query();
        $searchField = $request->query('search_field', 'full_name');
        $searchValue = $request->query('search_value');
        $validType = $request->query('valid_type');

        if (isset($searchValue)) {
            $query->where($searchField, 'ilike', Str::lower($searchValue) . '%');
        }

        if (isset($validType)) {
            switch ($validType) {
                case 'no-valid':
                    $query->whereHas('certification', function ($query) {
                        $query->where('is_valid', false);
                    });
                    break;
                case 'new-request':
                    $query->whereHas('certification', function ($query) {
                        $query->where('is_request_new', true);
                    });
                    break;
            }
        }


        $staffs = $query->with('certification')->get();
        return Inertia::render('Staff/Index', [
            'staffs' => $staffs
        ]);
    }

    public function store(CreateStaffRequest $request)
    {
        $data = $request->validated();
        $certificateReader = new ReadCertificate();

        if ($data['is_package']) {
            $certificateReader->readMany($data['certificate']);
        } else {
            $certificateReader->readSingle($data['certificate']);
        }

        return Inertia::render('Staff/Index', [
            'staffs' => Staff::all()
        ]);
    }

    public function show(Staff $staff)
    {
        $staff = $staff->load('certification');
        return Inertia::render('Staff/Show', [
            'staff' => $staff
        ]);
    }
}
