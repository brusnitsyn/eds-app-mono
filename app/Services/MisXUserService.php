<?php

namespace App\Services;

use App\Data\Mis\XUserMis;
use App\Facades\MisDoctor;
use App\Models\MisLPUDoctorToUserID;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

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
        'x_User.UserID', 'x_User.GeneralLogin', 'x_User.GeneralPassword', 'x_User.AuthMode', 'x_User.FIO', 'x_User.GUID'
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

    public function getUserByGuid(string $userGuid): ?XUserMis
    {
        $wheres = [
            [
                'column' => 'GUID',
                'operator' => '=',
                'value' => $userGuid
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
            $xUser = $this->getUserByDoctorPcod($doctor->code);
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

    /**
     * @throws \Throwable
     */
    public function createUser(array $data): ?XUserMis
    {
        $data = XUserMis::from($data);
        $builder = $this->getBaseBuilder();

        DB::transaction(function () use ($builder, $data) {
            if (empty($data->UserID)) {
                $data->GUID = Str::uuid()->toString();
                $data->GeneralPassword = $this->computeHash('1234567', $data->GUID);

                $builder->insert([
                    ...$data->except('UserID')->toArray()
                ]);
            } else {
                $builder->updateOrInsert(
                    ['UserID' => $data->UserID],
                    $data->except('UserID')->toArray()
                );
            }
        });

        $user = $this->getUserByGuid($data->GUID);

        return $user;
    }

    public function assignToDoctor(XUserMis $user, $doctorCode)
    {
        $settings = [
            [
                'rf_UserID' => $user->UserID,
                'Property' => 'Код врача',
                'DocTypeDefGUID' => Uuid::NIL,
                'ValueStr' => $doctorCode,
                'rf_SettingTypeID' => 7,
                'OwnerGUID' => $user->GUID
            ],
            [
                'rf_UserID' => $user->UserID,
                'Property' => 'Автоопределение врача',
                'DocTypeDefGUID' => Uuid::NIL,
                'ValueInt' => 1,
                'rf_SettingTypeID' => 8,
                'OwnerGUID' => $user->GUID
            ],
            [
                'rf_UserID' => $user->UserID,
                'Property' => 'Использование формы мед. документа',
                'DocTypeDefGUID' => Uuid::NIL,
                'ValueInt' => 1,
                'rf_SettingTypeID' => 8,
                'OwnerGUID' => $user->GUID
            ],
        ];

        $hasInsert = false;

        foreach ($settings as $setting) {
            DB::transaction(function () use ($setting, $hasInsert) {
                $hasInsert = DB::connection('mis')
                    ->table('x_UserSettings')
                    ->insert($setting);
            });
        }


        return $hasInsert;
    }

    public function getLastId()
    {
        return DB::connection('mis')
            ->selectOne("SELECT IDENT_CURRENT('$this->table') as UserID")
            ->UserID;
    }

    public function formatedLogin($fam, $ot, $im) : string
    {
        $fam = Str::title($fam);
        $im = Str::upper(Str::take($im, 1) ?: '');
        $ot = Str::upper(Str::take($ot, 1) ?: '');
        return "$fam$im$ot";
    }

    private function computeHash(string $password, string $guid)
    {
        if (!Str::isUuid($guid)) {
            Log::warning('Invalid GUID format provided', ['guid' => $guid]);
            throw new \InvalidArgumentException('The provided GUID is not valid');
        }

        $text = strtoupper($guid);
        $bytes = $text . $password . $text;

        // Хеширование с использованием Laravel's hash фасада
        $hash = sha1($bytes, true);

        // 4-1 итерации повторного хеширования
        for ($i = 0; $i < 3; $i++) {
            $hash = sha1($hash, true);
        }

        return base64_encode($hash);
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
