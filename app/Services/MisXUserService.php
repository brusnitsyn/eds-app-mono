<?php

namespace App\Services;

use App\Data\Mis\XUserMis;
use App\Facades\MisDoctor;
use App\Models\MisLPUDoctorToUserID;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class MisXUserService
{
    // Основная таблица для изменений
    protected string $table = 'x_User';
    protected array $relations = [
        'UserSettings' => [
            'table' => 'x_UserSettings',
            'first' => 'x_UserSettings.rf_UserID',
            'operator' => '=',
            'second' => 'x_User.UserID'
        ],
    ];
    protected array $baseSelect = [
        'x_User.UserID', 'x_User.GeneralLogin', 'x_User.GeneralPassword', 'x_User.GUID'
    ];

    public function getUserById(int $userId): ?XUserMis
    {
        $wheres = [
            [
                'column' => 'UserID',
                'operator' => '=',
                'value' => $userId
            ]
        ];
        $builder = $this->getBaseBuilder(null, $this->baseSelect, $wheres);
        $xUser = $builder->first();

        if (empty($xUser)) {
            return null;
        }

        return XUserMis::from($xUser);
    }

    public function getUserByDoctorId(int $doctorId): ?XUserMis
    {
        $storedUserID = MisLPUDoctorToUserID::where('lpu_doctor_id', $doctorId)->first();

        if (!empty($storedUserID)) {
            $xUser = $this->getUserById($storedUserID->user_id);
        } else {
            $doctor = MisDoctor::getDoctorById($doctorId);
            $xUser = $this->getUserByDoctorPcod($doctor->PCOD);
        }

        if (empty($xUser)) {
            return null;
        }

        MisLPUDoctorToUserID::updateOrCreate(['user_id' => $xUser->UserID], [
            'user_id' => $xUser->UserID,
            'lpu_doctor_id' => $doctorId
        ]);

        return XUserMis::from($xUser);
    }

    public function getUserByDoctorPcod(string $doctorPcod): ?XUserMis
    {
        $relations = ['UserSettings'];
        $wheres = [
            [
                'column' => 'x_UserSettings.Property',
                'operator' => '=',
                'value' => 'Код врача'
            ],
            [
                'column' => 'x_UserSettings.ValueStr',
                'operator' => '=',
                'value' => $doctorPcod
            ],
        ];
        $builder = $this->getBaseBuilder($relations, $this->baseSelect, $wheres);
        $xUser = $builder->first();

        if (empty($xUser)) {
            return null;
        }

        return XUserMis::from($xUser);
    }

    public function createUser(array $data): ?XUserMis
    {
        $data = XUserMis::from($data);
        $builder = $this->getBaseBuilder();

        DB::transaction(function () use ($builder, $data) {
            $builder->updateOrInsert(
                ['UserID' => $data->UserID],
                $data->toArray()
            );
        });

        $lastId = $this->getLastId();
        return $this->getUserById($lastId);
    }

    public function getLastId()
    {
        return DB::connection('mis')
            ->selectOne("SELECT IDENT_CURRENT('$this->table') as UserID")
            ->UserID;
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
