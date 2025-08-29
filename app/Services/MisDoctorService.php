<?php

namespace App\Services;

use App\Data\Mis\DoctorData;
use App\Data\Mis\InsertLPUDoctorData;
use App\Data\Mis\InsertPRVDDoctorData;
use App\Data\Mis\LpuDoctorData;
use App\Data\Mis\PrvdData;
use App\Facades\MisClassifier;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

class MisDoctorService
{
    // Основная таблица для изменений
    protected string $doctorTable = 'hlt_LPUDoctor';
    // Таблица должностей
    protected string $prvdTable = 'hlt_DocPRVD';

    protected array $relations = [
        'hlt_LPUDoctor' => [
            'oms_PRVS' => [
                'table' => 'oms_PRVS',
                'first' => 'hlt_LPUDoctor.rf_PRVSID',
                'operator' => '=',
                'second' => 'oms_PRVS.PRVSID'
            ],
            'oms_LPU' => [
                'table' => 'Oms_LPU',
                'first' => 'hlt_LPUDoctor.rf_LPUID',
                'operator' => '=',
                'second' => 'oms_LPU.LPUID'
            ],
            'oms_Department' => [
                'table' => 'oms_Department',
                'first' => 'hlt_LPUDoctor.rf_DepartmentID',
                'operator' => '=',
                'second' => 'oms_Department.DepartmentID'
            ],
            'oms_PRVD' => [
                'table' => 'oms_PRVD',
                'first' => 'hlt_LPUDoctor.rf_PRVDID',
                'operator' => '=',
                'second' => 'oms_PRVD.PRVDID'
            ],
        ],
        'hlt_DocPRVD' => []
    ];
    protected array $baseSelect = [
        'hlt_LPUDoctor.LPUDoctorID', 'hlt_LPUDoctor.PCOD', 'hlt_LPUDoctor.OT_V', 'hlt_LPUDoctor.IM_V',
        'hlt_LPUDoctor.FAM_V', 'hlt_LPUDoctor.DR', 'hlt_LPUDoctor.SS', 'hlt_LPUDoctor.UGUID',
        'hlt_LPUDoctor.isDoctor', 'hlt_LPUDoctor.inTime', 'hlt_LPUDoctor.isSpecial', 'hlt_LPUDoctor.isDismissal',
        'oms_PRVS.C_PRVS', 'oms_PRVS.PRVS_NAME', 'hlt_LPUDoctor.DateBegin', 'hlt_LPUDoctor.DateEnd',
        'Oms_LPU.M_NAMES', 'oms_Department.DepartmentName',
        'oms_PRVD.NAME', 'hlt_LPUDoctor.rf_PRVSID', 'hlt_LPUDoctor.rf_LPUID', 'hlt_LPUDoctor.rf_PRVDID',
        'hlt_LPUDoctor.rf_DepartmentID'
    ];

    protected array $relationsPrvd = [];
    protected array $baseSelectPrvd = [
        'hlt_DocPRVD.DocPRVDID',
        'hlt_DocPRVD.rf_LPUDoctorID',
        'hlt_DocPRVD.D_PRIK',
        'hlt_DocPRVD.S_ST',
        'hlt_DocPRVD.D_END',
        'hlt_DocPRVD.rf_PRVSID',
        'hlt_DocPRVD.rf_HealingRoomID',
        'hlt_DocPRVD.rf_DepartmentID',
        'hlt_DocPRVD.MainWorkPlace',
        'hlt_DocPRVD.InTime',
        'hlt_DocPRVD.GUID',
        'hlt_DocPRVD.rf_PRVDID',
        'hlt_DocPRVD.Name',
        'hlt_DocPRVD.ShownInSchedule',
        'hlt_DocPRVD.isDismissal',
        'hlt_DocPRVD.isSpecial',
        'hlt_DocPRVD.PCOD',
        'hlt_DocPRVD.rf_kl_FrmrPrvsID',
        'hlt_DocPRVD.rf_kl_DepartmentProfileID',
        'hlt_DocPRVD.rf_kl_DepartmentTypeID',
    ];


    /*
     * Получение доктора (LPUDoctor) по его коду должности
     */
    public function getDoctorByPcod(string $code): DoctorData
    {
        $relations = ['oms_PRVS', 'oms_PRVD', 'oms_LPU', 'oms_Department'];
        $selects = [
            ...$this->baseSelect
        ];
        $wheres = [
            [
                'column' => 'hlt_LPUDoctor.PCOD',
                'operator' => '=',
                'value' => $code
            ]
        ];

        $userBuilder = $this->getBaseBuilder($this->doctorTable, $relations, $selects, $wheres);

        $doctor = $userBuilder->first();

        if (empty($doctor)) {
            throw new \Error('MisDoctor not found');
        }

        $doctor = DoctorData::from($doctor);

        return $doctor;
    }
    public function getDoctorById(int $id): DoctorData
    {
        $relations = ['oms_PRVS', 'oms_PRVD', 'oms_LPU', 'oms_Department'];
        $selects = [
            ...$this->baseSelect
        ];
        $wheres = [
            [
                'column' => 'hlt_LPUDoctor.LPUDoctorID',
                'operator' => '=',
                'value' => $id
            ]
        ];

        $userBuilder = $this->getBaseBuilder($this->doctorTable, $relations, $selects, $wheres);

        $doctor = $userBuilder->first();

        if (empty($doctor)) {
            throw new \Error('MisDoctor not found');
        }

        $doctor = DoctorData::from($doctor);

        return $doctor;
    }
    public function getDoctorByGuid(string $guid): DoctorData
    {
        $relations = ['oms_PRVS', 'oms_PRVD', 'oms_LPU', 'oms_Department'];
        $selects = [
            ...$this->baseSelect
        ];
        $wheres = [
            [
                'column' => 'hlt_LPUDoctor.UGUID',
                'operator' => '=',
                'value' => $guid
            ]
        ];

        $userBuilder = $this->getBaseBuilder($this->doctorTable, $relations, $selects, $wheres);

        $doctor = $userBuilder->first();

        if (empty($doctor)) {
            throw new \Error('MisDoctor not found');
        }

        $doctor = DoctorData::from($doctor);

        return $doctor;
    }
    public function getPaginate(string|null $searchValue, int $pageSize): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $selects = [
            'LPUDoctorID', 'PCOD', 'OT_V', 'IM_V', 'FAM_V', 'DR', 'SS'
        ];
        $wheres = [
            [
                'column' => 'LPUDoctorID',
                'operator' => '<>',
                'value' => 0
            ]
        ];

        $builder = $this->getBaseBuilder($this->doctorTable, null, $selects, $wheres);

        if (!empty($searchValue)) {
            if (intval($searchValue) != 0) {
                $builder->when($searchValue, function ($query) use ($searchValue) {
                    $query->where("PCOD", 'like', "$searchValue%");
                });
            } else {
                $builder->when($searchValue, function ($query) use ($searchValue) {
                    $query->whereRaw("CONCAT(FAM_V, ' ', IM_V, ' ', OT_V) LIKE ?", ["$searchValue%"]);
                });
            }
        }

        $paginated = $builder->paginate($pageSize)->through(function ($item) {
            return [
                'id' => $item->LPUDoctorID,
                'code' => $item->PCOD,
                'middle_name' => $item->OT_V,
                'first_name' => $item->IM_V,
                'last_name' => $item->FAM_V,
                'brith_at' => $item->DR,
                'snils' => $item->SS,
            ];
        });

        return $paginated;
    }

    /**
     * @throws \Throwable
     */
    public function createDoctor(DoctorData $data, bool $hasCreatePrvd = false): DoctorData
    {
        $doctorGuid = $data->guid;
        $prvdGuid = Str::uuid();

        try {
            DB::connection('mis')->beginTransaction();

            $hasInsert = $this->getBaseBuilder($this->doctorTable)
                ->insert($data->except('rate', 'id')->toArray());

            if ($hasInsert === false) {
                throw new \Exception('Ошибка при вставки');
            }

            $doctor = $this->getDoctorByGuid($doctorGuid);

            if (!$hasCreatePrvd) {
                DB::connection('mis')->commit();
                return $doctor;
            }

            $doctorPrvds = $this->getPrvd($doctor->id)->count();

            $prvd = MisClassifier::getPrvd()->where('id', $data->prvd_id)->first();

//            department_profile_id, department_type_id, main_work_place, in_time, shown_in_schedule, is_dismissal, is_special
            $doctorPrvd = PrvdData::from([
                'doctor_id' => $doctor->id,
                'code' => $doctor->code . '-' . $doctorPrvds + 1,
                'start_at' => CarbonImmutable::now()->toDateTime(),
                'resource_type_id' => 1,
                'rate' => 1.00,
                'in_time' => 0,
                'is_special' => 0,
                'is_dismissal' => 0,
                'shown_in_schedule' => 0,
                'main_work_place' => 1,
                'prvs_id' => $data->prvs_id,
                'department_id' => $data->department_id,
                'prvd_id' => $data->prvd_id,
                'guid' => $prvdGuid,
                'frmr_prvd_id' => $prvd->code,
                'name' => $prvd->name,
            ]);

            $this->createPrvd($doctorPrvd);

            DB::connection('mis')->commit();

            return $doctor;
        } catch (\Exception $e) {
            DB::connection('mis')->rollBack();
            Log::error($e->getMessage());
            throw new \Exception('Ошибка при вставки');
        }
    }

    public function updateDoctor(DoctorData $data): DoctorData
    {
        try {
            DB::connection('mis')->beginTransaction();

            $hasUpdate = $this->getBaseBuilder($this->doctorTable)
                ->where('UGUID', $data->guid)
                ->update(...$data->except('rate')->toArray());

            $doctor = $this->getDoctorByGuid($data->guid);

            DB::connection('mis')->commit();

            return $doctor;
        } catch (\Exception $e) {
            DB::connection('mis')->rollBack();
            Log::error($e->getMessage());
            throw new \Exception('Ошибка при вставки');
        }
    }

    /**
     * @throws \Throwable
     */
    public function createPrvd(PrvdData $data) : PrvdData|bool
    {
        $builder = $this->getBaseBuilder($this->prvdTable);
        DB::transaction(function () use ($builder, $data) {
            $data = $data->toArray();
            if (empty($data['DocPRVDID'])) {
                $builder->insert([
                    ...$data
                ]);
            } else {
                $builder->updateOrInsert(
                    ['DocPRVDID' => $data['DocPRVDID']],
                    $data
                );
            }
        });

        $prvd = $this->getPrvd(guid: $data->guid);

        return $prvd;
    }

    public function getPrvd(int $doctor_id = null, int $id = null, string $code = null, string $guid = null) : Collection|PrvdData|bool
    {
        $wheres = [];

        if (!empty($doctor_id)) {
            $wheres = [
                [
                    'column' => "$this->prvdTable.rf_LPUDoctorID",
                    'operator' => '=',
                    'value' => $doctor_id
                ]
            ];

            $prvds = $this->getBaseBuilder($this->prvdTable, null, $this->baseSelectPrvd, $wheres)->get()
                ->map(function ($prvd) {
                    return PrvdData::from($prvd)->toOriginal();
                });

            if (empty($prvds)) {
                return false;
            }

            return $prvds;
        }
        if (!empty($id)) {
            $wheres = [
                [
                    'column' => "$this->prvdTable.DocPRVDID",
                    'operator' => '=',
                    'value' => $id
                ]
            ];
        }
        if (!empty($code)) {
            $wheres = [
                [
                    'column' => "$this->prvdTable.PCOD",
                    'operator' => '=',
                    'value' => $code
                ]
            ];
        }
        if (!empty($guid)) {
            $wheres = [
                [
                    'column' => "$this->prvdTable.GUID",
                    'operator' => '=',
                    'value' => $guid
                ]
            ];
        }

        $prvd = $this->getBaseBuilder($this->prvdTable, null, $this->baseSelectPrvd, $wheres)->first();

        if (empty($prvd)) {
            return false;
        }

        return PrvdData::from($prvd);
    }

    public function getSearchedDoctor(string $fio, string|null $pcod = null)
    {

    }

    public function getBaseBuilder(
        string $table = null,
        array|null $relations = null,
        array|null $select = null,
        array|null $wheres = null): \Illuminate\Database\Query\Builder
    {
        $builder = DB::connection('mis')
            ->table($table);

        // Обработка relations
        if (!empty($relations)) {
            $relationsTable = Arr::get($this->relations, $table);
            foreach ($relations as $relationKey) {

                $relation = Arr::get($relationsTable, $relationKey);

                if (is_null($relation)) {
                    throw new \Error("Связь $relationKey не найдена");
                }

                $builder->join(...$relation);
            }
        }

        // Обработка select
        if (!empty($select)) {
            $builder->select($select);
        }

        // Обработка where
        if (!empty($wheres)) {
            foreach ($wheres as $where) {
                $builder->where(...$where);
            }
        }

        return $builder;
    }
}
