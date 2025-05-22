<?php

namespace App\Services;

use App\Data\Mis\LpuDoctorData;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class MisDoctorService
{
    // Основная таблица для изменений
    protected string $table = 'hlt_LPUDoctor';
    protected array $relations = [
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
    ];
    protected array $baseSelect = [
        'hlt_LPUDoctor.LPUDoctorID', 'hlt_LPUDoctor.PCOD', 'hlt_LPUDoctor.OT_V', 'hlt_LPUDoctor.IM_V',
        'hlt_LPUDoctor.FAM_V', 'hlt_LPUDoctor.D_SER', 'hlt_LPUDoctor.DR', 'hlt_LPUDoctor.SS',
        'hlt_LPUDoctor.isDoctor', 'hlt_LPUDoctor.inTime', 'hlt_LPUDoctor.isSpecial', 'hlt_LPUDoctor.isDismissal',
        'oms_PRVS.C_PRVS', 'oms_PRVS.PRVS_NAME', 'oms_PRVS.Date_Beg', 'oms_PRVS.Date_End',
        'Oms_LPU.M_NAMES', 'oms_Department.DepartmentName',
        'oms_PRVD.NAME', 'hlt_LPUDoctor.rf_PRVSID', 'hlt_LPUDoctor.rf_LPUID', 'hlt_LPUDoctor.rf_PRVDID',
        'hlt_LPUDoctor.rf_DepartmentID'
    ];

    /*
     * Получение доктора (LPUDoctor) по его коду должности
     */
    public function getDoctorByPcod(string $pcod): LpuDoctorData
    {
        $relations = ['oms_PRVS', 'oms_PRVD', 'oms_LPU', 'oms_Department'];
        $selects = [
            ...$this->baseSelect
        ];
        $wheres = [
            [
                'column' => 'hlt_LPUDoctor.PCOD',
                'operator' => '=',
                'value' => $pcod
            ]
        ];

        $userBuilder = $this->getBaseBuilder($relations, $selects, $wheres);

        $doctor = $userBuilder->first();

        if (empty($doctor)) {
            throw new \Error('MisDoctor not found');
        }

        $doctor = LpuDoctorData::from($doctor);

        return $doctor;
    }

    public function getDoctorById(int $id): LpuDoctorData
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

        $userBuilder = $this->getBaseBuilder($relations, $selects, $wheres);

        $doctor = $userBuilder->first();

        if (empty($doctor)) {
            throw new \Error('MisDoctor not found');
        }

        $doctor = LpuDoctorData::from($doctor);

        return $doctor;
    }

    public function getPaginate(string|null $searchValue, int $pageSize): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $selects = [
            'LPUDoctorID', 'PCOD', 'OT_V', 'IM_V', 'FAM_V', 'D_SER', 'DR', 'SS'
        ];
        $wheres = [
            [
                'column' => 'LPUDoctorID',
                'operator' => '<>',
                'value' => 0
            ]
        ];

        $builder = $this->getBaseBuilder(null, $selects, $wheres);

        if (!empty($searchValue)) {
            if (is_int(intval($searchValue))) {
                $builder->when($searchValue, function ($query) use ($searchValue) {
                    $query->where("PCOD", 'like', "$searchValue%");
                });
            } else {
                $builder->when($searchValue, function ($query) use ($searchValue) {
                    $query->whereRaw("CONCAT(FAM_V, ' ', IM_V, ' ', OT_V) LIKE ?", ["$searchValue%"]);
                });
            }
        }

        return $builder->paginate($pageSize);
    }

    public function getSearchedDoctor(string $fio, string|null $pcod = null)
    {

    }

    public function getBaseBuilder(
        array|null $relations = null,
        array|null $select = null,
        array|null $wheres = null): \Illuminate\Database\Query\Builder
    {
        $builder = DB::connection('mis')
            ->table($this->table);

        // Обработка relations
        if (!empty($relations)) {
            foreach ($relations as $relationKey) {

                $relation = Arr::get($this->relations, $relationKey);

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
