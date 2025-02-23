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
        // Получаем фильтры из запроса
        $filters = $request->input('filters', []);

        $searchValue = $request->query('search_value');
        if ($searchValue)
            $query = Staff::search($searchValue);
        else
            $query = Staff::query();

        if (isset($filters['job_title']) && is_array($filters['job_title'])) {
            $query->whereIn('job_title', $filters['job_title']);
        }

        $validType = $request->query('valid_type');
        $searchWhereParams = collect();
        if (isset($validType)) {
            switch ($validType) {
                case 'no-valid':
                    $searchWhereParams->push('is_valid', 0);
                    break;
                case 'new-request':
                    $searchWhereParams->push('is_request_new', 1);
                    break;
            }
        }

        // Если фильтр не пуст
        if ($searchWhereParams->isNotEmpty()) {
            $searchWhereParams = $searchWhereParams->toArray();
            $query = $query->whereHas('certification', function ($query) use ($searchWhereParams) {
                $query->where($searchWhereParams[0], $searchWhereParams[1]);
            });
        }

        $query = $query->get();

        if ($query->isEmpty()) {
            return Inertia::render('Staff/Index', [
                'staffs' => []
            ]);
        }
        $query = $query->toQuery();

        $staffs = $query->with('certification')->get();

        $filterJob = $staffs->filter(function ($staff) {
            return [$staff->job_title => $staff->job_title];
        })->unique('job_title')->map(function ($staff) {
            return ['label' => $staff->job_title, 'value' => $staff->job_title];
        })->pluck(null);

        return Inertia::render('Staff/Index', [
            'staffs' => $staffs,
            'filterJob' => $filterJob,
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
