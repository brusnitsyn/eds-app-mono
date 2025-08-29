<?php

namespace App\Services;

use App\Data\Mis\Classifier\ClassifierDepartmentData;
use App\Data\Mis\Classifier\ClassifierDepartmentProfileData;
use App\Data\Mis\Classifier\ClassifierDepartmentTypeData;
use App\Data\Mis\Classifier\ClassifierLpuData;
use App\Data\Mis\Classifier\ClassifierPrvdData;
use App\Data\Mis\Classifier\ClassifierPrvsData;
use App\Data\Mis\Classifier\ClassifierHealingRoomData;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MisClassifierService
{
    public function getLpu(int|null $id = null, string|null $mcod = null, string|null $guid = null) : Collection|ClassifierLpuData
    {
        $lpus = Cache::get('mis_lpus', []);

        if (empty($lpus)) {
            $select = [
                'Oms_LPU.LPUID', 'Oms_LPU.MCOD', 'Oms_LPU.M_NAMES', 'Oms_LPU.DATE_E', 'Oms_LPU.GUIDLPU'
            ];
            $wheres = [
                [
                    'column' => 'Oms_LPU.LPUID',
                    'operator' => '=',
                    'value' => 844
                ]
            ];
            $mainLpu = ClassifierLpuData::from(
                $this->getBaseBuilder('Oms_LPU', select: $select, wheres: $wheres)->first()
            );

            $wheres = [
                [
                    'column' => 'Oms_LPU.rf_MainLPUID',
                    'operator' => '=',
                    'value' => 844
                ]
            ];
            $lpus = $this->getBaseBuilder('Oms_LPU', select: $select, wheres: $wheres)
                ->get()->map(function ($lpu) {
                    return ClassifierLpuData::from($lpu);
                });

            $lpus->prepend($mainLpu);

            // Кеширование результата запроса на 1 неделю
            Cache::put('mis_lpus', $lpus, now()->addWeek());
        }

        if (!empty($id)) {
            $lpuById = $lpus->where('id', $id)->first();
            return $lpuById;
        }

        if (!empty($mcod)) {
            $lpuByMcod = $lpus->where('code', $mcod)->first();
            return $lpuByMcod;
        }

        if (!empty($guid)) {
            $lpuByGuid = $lpus->where('guid', $guid)->first();
            return $lpuByGuid;
        }

        return $lpus;
    }

    public function getDepartment(int|null $id = null, string|null $code = null, string|null $guid = null) : Collection|ClassifierDepartmentData
    {
        $departments = Cache::get('mis_departments', []);

        if (empty($departments)) {
            $relations = [
                [
                    'table' => 'Oms_LPU',
                    'first' => 'oms_Department.rf_LPUID',
                    'operator' => '=',
                    'second' => 'Oms_LPU.LPUID',
                ]
            ];
            $select = [
                'oms_Department.DepartmentID', 'oms_Department.DepartmentCODE', 'oms_Department.DepartmentNAME',
                'oms_Department.rf_kl_DepartmentTypeID', 'oms_Department.rf_kl_DepartmentProfileID',
                'oms_Department.Date_E', 'oms_Department.GUIDDepartment'
            ];
            $wheres = [
                [
                    'column' => 'Oms_LPU.rf_MainLPUID',
                    'operator' => '=',
                    'value' => 844
                ],
                [
                    'type' => 'date',
                    'column' => 'oms_Department.Date_E',
                    'operator' => '>',
                    'value' => Carbon::now()->toDateString()
                ],
            ];

            $departments = $this->getBaseBuilder('oms_Department', $relations, $select, $wheres)
                ->get()->map(function ($department) {
                    return ClassifierDepartmentData::from($department);
                });

            // Кеширование результата запроса на 1 неделю
            Cache::put('mis_departments', $departments, now()->addWeek());
        }

        if (!empty($id)) {
            $departmentById = $departments->where('id', $id)->first();
            return $departmentById;
        }

        if (!empty($code)) {
            $departmentByCode = $departments->where('code', $code)->first();
            return $departmentByCode;
        }

        if (!empty($guid)) {
            $departmentByGuid = $departments->where('guid', $guid)->first();
            return $departmentByGuid;
        }

        return $departments;
    }

    public function getPrvd(int|null $id = null, string|null $code = null, string|null $guid = null) : Collection|ClassifierPrvdData
    {
        $prvds = Cache::get('mis_prvd', []);

        if (empty($prvds)) {
            $select = [
                'Oms_PRVD.PRVDID', 'Oms_PRVD.C_PRVD', 'Oms_PRVD.NAME', 'Oms_PRVD.Date_E', 'Oms_PRVD.PRVDGuid'
            ];
            $wheres = [
                [
                    'column' => 'Oms_PRVD.PRVDID',
                    'operator' => '<>',
                    'value' => 0
                ],
                [
                    'type' => 'date',
                    'column' => 'Oms_PRVD.Date_E',
                    'operator' => '>',
                    'value' => Carbon::now()->toDateString()
                ],
            ];

            $prvds = $this->getBaseBuilder('Oms_PRVD', select: $select, wheres: $wheres)
                ->get()->map(function ($prvd) {
                    return ClassifierPrvdData::from($prvd);
                });

            // Кеширование результата запроса на 1 неделю
            Cache::put('mis_prvd', $prvds, now()->addWeek());
        }

        if (!empty($id)) {
            $prvdById = $prvds->where('id', $id)->first();
            return $prvdById;
        }

        if (!empty($code)) {
            $prvdByCode = $prvds->where('code', $code)->first();
            return $prvdByCode;
        }

        if (!empty($guid)) {
            $prvdByGuid = $prvds->where('guid', $guid)->first();
            return $prvdByGuid;
        }

        return $prvds;
    }

    public function getPrvs(int|null $id = null, string|null $code = null, string|null $guid = null) : Collection|ClassifierPrvsData
    {
        $prvss = Cache::get('mis_prvs', []);

        if (empty($prvss)) {
            $select = [
                'Oms_PRVS.PRVSID', 'Oms_PRVS.C_PRVS', 'Oms_PRVS.PRVS_NAME', 'Oms_PRVS.Date_End', 'Oms_PRVS.PRVSGuid'
            ];
            $wheres = [
                [
                    'column' => 'Oms_PRVS.PRVSID',
                    'operator' => '<>',
                    'value' => 0
                ],
                [
                    'type' => 'date',
                    'column' => 'Oms_PRVS.Date_End',
                    'operator' => '>',
                    'value' => Carbon::now()->toDateString()
                ],
            ];

            $prvss = $this->getBaseBuilder('Oms_PRVS', select: $select, wheres: $wheres)
                ->get()->map(function ($prvs) {
                    return ClassifierPrvsData::from($prvs);
                });

            // Кеширование результата запроса на 1 неделю
            Cache::put('mis_prvs', $prvss, now()->addWeek());
        }

        if (!empty($id)) {
            $prvsById = $prvss->where('id', $id)->first();
            return $prvsById;
        }

        if (!empty($code)) {
            $prvsByCode = $prvss->where('code', $code)->first();
            return $prvsByCode;
        }

        if (!empty($guid)) {
            $prvsByGuid = $prvss->where('guid', $guid)->first();
            return $prvsByGuid;
        }

        return $prvss;
    }

    public function getDepartmentType(int|null $id = null, string|null $code = null, string|null $guid = null) : Collection|ClassifierDepartmentTypeData
    {
        $departmentTypes = Cache::get('mis_department_types', []);

        if (empty($departmentTypes)) {
            $select = [
                'oms_kl_DepartmentType.kl_DepartmentTypeID', 'oms_kl_DepartmentType.Code', 'oms_kl_DepartmentType.Name',
                'oms_kl_DepartmentType.Date_E', 'oms_kl_DepartmentType.DepartmentTypeGuid'
            ];
            $wheres = [
                [
                    'type' => 'date',
                    'column' => 'oms_kl_DepartmentType.Date_E',
                    'operator' => '>',
                    'value' => Carbon::now()->toDateString()
                ]
            ];

            $departmentTypes = $this->getBaseBuilder('oms_kl_DepartmentType', select: $select, wheres: $wheres)
                ->get()->map(function ($departmentType) {
                    return ClassifierDepartmentTypeData::from($departmentType);
                });

            // Кеширование результата запроса на 1 неделю
            Cache::put('mis_department_types', $departmentTypes, now()->addWeek());
        }

        if (!empty($id)) {
            $departmentTypeById = $departmentTypes->where('id', $id)->first();
            return $departmentTypeById;
        }

        if (!empty($mcod)) {
            $departmentTypeByMcod = $departmentTypes->where('code', $code)->first();
            return $departmentTypeByMcod;
        }

        if (!empty($guid)) {
            $departmentTypeByGuid = $departmentTypes->where('guid', $guid)->first();
            return $departmentTypeByGuid;
        }

        return $departmentTypes;
    }

    public function getDepartmentProfile(int|null $id = null, string|null $code = null) : Collection|ClassifierDepartmentProfileData
    {
        $departmentProfiles = Cache::get('mis_department_profiles', []);

        if (empty($departmentProfiles)) {
            $select = [
                'oms_kl_DepartmentProfile.kl_DepartmentProfileID', 'oms_kl_DepartmentProfile.Code', 'oms_kl_DepartmentProfile.Name',
                'oms_kl_DepartmentProfile.Date_E'
            ];
            $wheres = [
                [
                    'type' => 'date',
                    'column' => 'oms_kl_DepartmentProfile.Date_E',
                    'operator' => '>',
                    'value' => Carbon::now()->toDateString()
                ]
            ];

            $departmentProfiles = $this->getBaseBuilder('oms_kl_DepartmentProfile', select: $select, wheres: $wheres)
                ->get()->map(function ($departmentProfile) {
                    return ClassifierDepartmentProfileData::from($departmentProfile);
                });

            // Кеширование результата запроса на 1 неделю
            Cache::put('mis_department_profiles', $departmentProfiles, now()->addWeek());
        }

        if (!empty($id)) {
            $departmentProfileById = $departmentProfiles->where('id', $id)->first();
            return $departmentProfileById;
        }

        if (!empty($code)) {
            $departmentProfileByMcod = $departmentProfiles->where('code', $code)->first();
            return $departmentProfileByMcod;
        }

        return $departmentProfiles;
    }

    public function getHealingRoom(int|null $departmentId, int|null $id = null) : Collection|ClassifierHealingRoomData
    {
        $healingRooms = Cache::get('mis_healing_rooms', []);

        if (empty($healingRooms)) {
            $select = [
                'HealingRoomID', 'Num', 'Comment', 'UGUID', 'InTime', 'rf_DepatementID', 'DateBegin', 'DateEnd'
            ];
            $wheres = [
                [
                    'type' => 'date',
                    'column' => 'DateEnd',
                    'operator' => '>',
                    'value' => Carbon::now()->toDateString()
                ]
            ];

            $healingRooms = $this->getBaseBuilder('hlt_HealingRoom', select: $select, wheres: $wheres)
                ->get()->map(function ($healingRoom) {
                    return ClassifierHealingRoomData::from($healingRoom);
                });

            // Кеширование результата запроса на 1 неделю
            Cache::put('mis_healing_rooms', $healingRooms, now()->addWeek());
        }

        if (!empty($departmentId)) {
            $healingRoomByDepartmentId = $healingRooms->where('department_id', $departmentId)->first();
            return $healingRoomByDepartmentId;
        }

        if (!empty($id)) {
            $healingRoomById = $healingRooms->where('id', $id)->first();
            return $healingRoomById;
        }

        return $healingRooms;
    }

    private function getBaseBuilder(
        string $table,
        array|null $relations = null,
        array|null $select = null,
        array|null $wheres = null)
    {
        $builder = DB::connection('mis')
            ->table($table);

        // Обработка relations
        if (!empty($relations)) {
            foreach ($relations as $relation) {
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
                if (Arr::exists($where, 'type') && $where['type'] === 'date') {
                    $builder->whereDate(...Arr::except($where, 'type'));
                    continue;
                }
                $builder->where(...$where);
            }
        }

        return $builder;
    }
}
