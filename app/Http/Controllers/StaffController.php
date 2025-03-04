<?php

namespace App\Http\Controllers;

use App\Actions\Eds\ReadCertificate;
use App\Facades\Crypto;
use App\Http\Requests\Staff\CreateStaffRequest;
use App\Models\Certification;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Inertia\Inertia;
use PhpZip\ZipFile;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        // Получаем фильтры из запроса
        $filters = $request->input('filters', []);
        $searchValue = (string) $request->query('search_value');
        $validType = $request->query('valid_type');
        $pageSize = $request->query('page_size', 25);

        // Определяем, используем ли мы Scout или обычный запрос
        $isScoutSearch = !empty($searchValue);

        // Создаем запрос
        $query = $isScoutSearch ? Staff::search($searchValue) : Staff::query();

        // Применяем фильтр по должности
        if (isset($filters['job_title']) && is_array($filters['job_title'])) {
            $query->whereIn('job_title', $filters['job_title']);
        }

        // Если это Scout-запрос, сначала получаем результаты, затем фильтруем и загружаем отношения
        if ($isScoutSearch) {
            $staffs = $query->get();

            // Фильтруем по сертификации, если указан validType
            if (isset($validType)) {
                $staffs = $staffs->filter(function ($staff) use ($validType) {
                    return $staff->certification()->where(function ($q) use ($validType) {
                        if ($validType == 'no-valid') {
                            $q->where('is_valid', false);
                        } else if ($validType == 'new-request') {
                            $q->where('is_request_new', true);
                        }
                    })->exists();
                });
            }

            // Загружаем отношение certification
            $staffs->load('certification');

            // Пагинация вручную
            $staffs = new LengthAwarePaginator(
                $staffs->forPage(request('page', 1), $pageSize),
                $staffs->count(),
                $pageSize,
                request('page', 1)
            );
        } else {
            // Если это обычный Eloquent-запрос, используем with и whereHas
            $query->with('certification');

            if (isset($validType)) {
                $query->whereHas('certification', function ($query) use ($validType) {
                    if ($validType == 'no-valid') {
                        $query->where('is_valid', false);
                    } else if ($validType == 'new-request') {
                        $query->where('is_request_new', true);
                    }
                });
            }

            // Пагинация через Eloquent
            $staffs = $query->paginate($pageSize);
        }

        // Если нет результатов, возвращаем пустой массив
        if ($staffs->isEmpty()) {
            return Inertia::render('Staff/Index', [
                'staffs' => []
            ]);
        }

        // Фильтр для должностей
        $filterJob = Staff::all()->filter(function ($staff) {
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

    public function downloadCertificates(Request $request, $staff_ids)
    {
        $staffIds = explode(',', $staff_ids);
        if (empty($staffIds)) {
            return;
        }

        $certificateIds = Staff::whereIn('id', $staffIds)
            ->get()->map(function ($staff) {
                return [$staff->certification->id];
            });

        if ($certificateIds->isEmpty()) {
            return;
        }

        $certificates = Certification::whereIn('id', $certificateIds)->get();

        if ($certificates->isEmpty()) {
            return;
        }

        $zipFileName = 'certificates_' . time() . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFileName);

        $zip = new ZipFile();

        foreach ($certificates as $certificate) {
            $filePath = \Storage::disk('certification')->files($certificate->path_certification, true);

            foreach ($filePath as $file) {
                $filePath = \Storage::disk('certification')->path($file);
                $fileContent = file_get_contents($filePath);
                $decrypt = Crypt::decryptString($fileContent);

                if (file_exists($filePath)) {
                    $fileName = pathinfo($file, PATHINFO_BASENAME);
                    if (Str::contains(dirname($file), "/"))
                    {
                        $fileName = Str::substr(dirname($file), Str::position(dirname($file), "/") + 1) . "/$fileName";
                    }

                    $zip->addFromString($fileName, $decrypt);
                }
            }
        }
        $zip->saveAsFile($zipPath);
        $zip->close();

        return response()->download($zipPath)->deleteFileAfterSend();
    }
}
