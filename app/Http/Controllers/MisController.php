<?php

namespace App\Http\Controllers;

use App\Data\Mis\Classifier\ClassifierDepartmentData;
use App\Data\Mis\Classifier\ClassifierDepartmentProfileData;
use App\Data\Mis\Classifier\ClassifierDepartmentTypeData;
use App\Data\Mis\Classifier\ClassifierLpuData;
use App\Data\Mis\Classifier\ClassifierPrvdData;
use App\Data\Mis\Classifier\ClassifierPrvsData;
use App\Data\Mis\DoctorData;
use App\Data\Mis\InsertPRVDDoctorData;
use App\Data\Mis\LpuDoctorData;
use App\Data\Mis\PrvdData;
use App\Data\Mis\XRole;
use App\Data\Mis\XUserRole;
use App\Facades\MisClassifier;
use App\Facades\MisDoctor;
use App\Facades\MisImport;
use App\Facades\MisXUser;
use App\Models\MisLPUDoctorToUserID;
use App\Models\MisPasswordHistory;
use App\Models\MisRoleTemplate;
use App\Models\Staff;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Ramsey\Uuid\Uuid;
use Spatie\LaravelData\Optional;
use stdClass;

class MisController extends Controller
{
    public function index(Request $request)
    {
        $usersCount = DB::connection('mis')
            ->table('hlt_LPUDoctor')
            ->where('LPUDoctorID', '<>', 0)
            ->count();

        return Inertia::render('MIS/Index', [
            'usersCount' => $usersCount
        ]);
    }

    public function users(Request $request)
    {
        $searchValue = $request->query('search_value');
        $pageSize = $request->query('page_size', 25);

        $users = MisDoctor::getPaginate($searchValue, $pageSize);

        return Inertia::render('MIS/Users/Index',
            [
                'users' => $users,
                'lpus' => $this->formattedLpus(),
                'departments' => $this->formattedDepartments(),
                'prvd' => $this->formattedPrvd(),
                'prvs' => $this->formattedPrvs(),
            ]);
    }

    public function user(int $userId, Request $request)
    {
        $user = MisDoctor::getDoctorById($userId)->toOriginal();
        $xUser = MisXUser::getUserByDoctorId($userId);
        $prvds = MisDoctor::getPrvd($userId);
        $snils = Str::replace('-', '', $user['snils']);
        $snils = Str::replace(' ', '', $snils);

        $staff = Staff::where('snils', '=', $snils)->first();

        $templates = MisRoleTemplate::with(['createUser'])->get();

        return Inertia::render('MIS/Users/Show', [
            'user' => $user,
            'x_user' => $xUser,
            'jobs' => $prvds,
            'staff' => $staff,
            'departments' => $this->formattedDepartments(),
            'prvd' => $this->formattedPrvd(),
            'prvs' => $this->formattedPrvs(),
            'lpus' => $this->formattedLpus(),
            'department_types' => $this->formattedDepartmentType(),
            'department_profiles' => $this->formattedDepartmentProfile(),
            'roles' => $this->getRoles(),
            'user_roles' => empty($xUser) ? [] : $this->getRolesByUserId($xUser->UserID),
            'role_templates' => $templates
        ]);
    }

    /**
     * @throws \Throwable
     */
    public function createUser(Request $request)
    {
        $data = DoctorData::factory()->withoutOptionalValues()->from([
            ...$request->all(),
            'start_at' => Optional::create(),
            'end_at' => Optional::create(),
            'guid' => Str::uuid(),
        ]);

        $data = $data
            ->except('prvs_code', 'prvs_name', 'lpu_name', 'department_name', 'prvd_name', 'has_password_change');

        $xUser = [
            'GeneralLogin' => $this->formatedLogin($data->last_name, $data->middle_name, $data->first_name),
            'AuthMode' => 1,
            'FIO' => "$data->last_name $data->first_name $data->middle_name",
        ];

        try {
            $user = MisXUser::createUser($xUser);
            $doctor = MisDoctor::createDoctor($data, true);
            $hasAssigned = MisXUser::assignToDoctor($user, $doctor->code);
        } catch (\Exception $ex) {
            Log::error("Ошибка при создании учетной записи: " . $ex->getMessage());
        }

//        DB::connection('mis')->beginTransaction();
//
//        try {
//            DB::connection('mis')
//                ->table('hlt_LPUDoctor')
//                ->insert(Arr::except($data, ['S_ST']));
//
//            $lpuDoctorId = DB::connection('mis')
//                ->selectOne("SELECT IDENT_CURRENT('hlt_LPUDoctor') as LPUDoctorID")
//                ->LPUDoctorID;
//
//            $dataPRVD = Arr::except($data, ['OT_V', 'IM_V', 'FAM_V', 'DR', 'SS', 'isDoctor', 'rf_LPUID']);
//            $dataPRVD['rf_LPUDoctorID'] = $lpuDoctorId;
//            $dataPRVD['D_END'] = Carbon::parse('2222-01-01 00:00:00.000')->toDateTimeLocalString();
//            $dataPRVD['rf_ResourceTypeID'] = 1;
//
//            $prvd = DB::connection('mis')
//                ->table('oms_PRVD')
//                ->select([
//                    'C_PRVD'
//                ])
//                ->where('PRVDID', '=', $data['rf_PRVDID'])
//                ->first();
//
//            $hasExistFrmrPrvd = DB::connection('mis')
//                ->table('oms_kl_FrmrPrvd')
//                ->where('Code', $prvd->C_PRVD)
//                ->exists();
//
//            $dataPRVD['rf_kl_FrmrPrvdID'] = $hasExistFrmrPrvd ? $prvd->C_PRVD : 0;
//
//            DB::connection('mis')
//                ->table('hlt_DocPRVD')
//                ->insert($dataPRVD);
//
//            $login = $this->formatedLogin($data['FAM_V'], $data['OT_V'], $data['IM_V']);
//
//            $xUser = [
//                'GeneralLogin' => $login,
//                'AuthMode' => 1,
//                'FIO' => "{$data['FAM_V']} {$data['IM_V']} {$data['OT_V']}",
//                'GeneralPassword' => ''
//            ];
//
//            DB::connection('mis')
//                ->table('x_User')
//                ->insert($xUser);
//
//            $createdXUser = DB::connection('mis')
//                ->table('x_User')
//                ->select(['UserID', 'GUID'])
//                ->where('GeneralLogin', '=', $login)
//                ->first();
//
//            $xUser['GeneralPassword'] = $this->computeHash('1234567', $createdXUser->GUID);
//
//            $updatedXUser = DB::connection('mis')
//                ->table('x_User')
//                ->where('UserID', '=', $createdXUser->UserID)
//                ->update($xUser);
//
////            select * from x_UserSettings
////            where Property = 'Код врача' and ValueStr = '246515'
//            $settingPcod = [
//                'rf_UserID' => (int)$createdXUser->UserID,
//                'Property' => 'Код врача',
//                'DocTypeDefGUID' => Uuid::NIL,
//                'ValueStr' => $data['PCOD'],
//                'rf_SettingTypeID' => 7,
//                'OwnerGUID' => $createdXUser->GUID
//            ];
//
//            DB::connection('mis')
//                ->table('x_UserSettings')
//                ->insert($settingPcod);
//
//            DB::connection('mis')->commit();
//
//        } catch (\Exception $e) {
//            DB::connection('mis')->rollBack();
//            throw $e;
//        }
        return redirect(route('mis.users'));
    }

    /**
     * @throws \Throwable
     */
    public function createPost(int $userId, Request $request)
    {
        $data = PrvdData::from([
            ...$request->all(),
            'guid' => Str::uuid(),
            'start_at' => CarbonImmutable::now()->format('Y-m-d H:i:s.u'),
            'end_at' => CarbonImmutable::create(2222, 01, 01)->format('Y-m-d H:i:s.u'),
        ]);

        $post = MisDoctor::createPrvd($data);

        return redirect(route('mis.user', ['userId' => $userId]));
    }

    public function updateUser(int $userId, Request $request)
    {
//        $data = $request->validate([
//            'PCOD' => ['required', 'string'],
//            'OT_V' => ['required', 'string'],
//            'IM_V' => ['required', 'string'],
//            'FAM_V' => ['required', 'string'],
//            'DR' => ['required', 'string'],
//            'SS' => ['required', 'string'],
//            'isDoctor' => ['required', 'boolean'],
//            'inTime' => ['required', 'boolean'],
//            'isSpecial' => ['required', 'boolean'],
//            'isDismissal' => ['required', 'boolean'],
//            'rf_LPUID' => ['required', 'numeric'],
//            'rf_PRVSID' => ['required', 'numeric'],
//            'rf_DepartmentID' => ['required', 'numeric'],
//            'rf_PRVDID' => ['required', 'numeric'],
//        ]);
//        $data['DR'] = Carbon::parse($data['DR'])->toDateTimeLocalString();
        $data = DoctorData::from($request->all());
        dd($data->toArray());

        $hasUpdated = DB::connection('mis')
            ->table('hlt_LPUDoctor')
            ->where('LPUDoctorID', '=', $userId)
            ->update($data);

        return redirect(route('mis.user', ['userId' => $userId]));
    }

    public function updateOrCreateAccess(int $doctorId, Request $request)
    {
        $data = $request->validate([
            'GeneralLogin' => ['required', 'string'],
            'GeneralPassword' => ['required', 'string'],
        ]);

        $doctor = DB::connection('mis')
            ->table('hlt_LPUDoctor')
            ->select(['PCOD'])
            ->where('LPUDoctorID', '=', $doctorId)
            ->first();

        $params = DB::connection('mis')
            ->table('x_UserSettings')
            ->select(['rf_UserID'])
            ->where('Property', '=', 'Код врача')
            ->where('ValueStr', '=', $doctor->PCOD)
            ->first();

        if (empty($params)) {
            $userId = null;
        } else {
            $userId = (int)$params->rf_UserID;
        }

        $user = DB::connection('mis')
            ->table('x_User')
            ->select(['GUID'])
            ->where('UserID', '=', $userId)
            ->first();

        $data['GeneralPassword'] = $this->computeHash($data['GeneralPassword'], $user->GUID);

        $hasCreatedOrUpdatedAccess = DB::connection('mis')
            ->table('x_User')
            ->where('UserID', '=', $userId)
            ->update([
                'GeneralLogin' => $data['GeneralLogin'],
                'GeneralPassword' => $data['GeneralPassword'],
            ]);

        return redirect(route('mis.user', ['userId' => $doctorId]));
    }

    public function updatePost(int $doctorId, Request $request)
    {
        $data = PrvdData::from($request->all());

        $prvd = DB::connection('mis')
            ->table('hlt_DocPRVD')
            ->where('DocPRVDID', '=', $data->id)
            ->update($data->except('id', 'guid')->toArray());

        return redirect(route('mis.user', ['userId' => $doctorId]));
    }

    public function updateRoles(int $userId, Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'numeric'],
            'roles' => ['required', 'array'],
        ]);

        $newRoles = collect($data['roles']);
        $userRoles = $this->getRolesByUserId($data['user_id'])->values();

        $deleteRoles = $userRoles->pluck('role_id')
            ->diff($newRoles)
            ->map(function ($role) use ($data) {
                return [
                    'UserID' => (int)$data['user_id'],
                    'RoleID' => $role,
                ];
            });
        $addRoles = $newRoles->diff($userRoles->pluck('role_id'))->map(function ($role) use ($data) {
            return [
                'UserID' => (int)$data['user_id'],
                'RoleID' => $role,
            ];
        })->toArray();

        if ($deleteRoles->count() > 0) {
            foreach ($deleteRoles as $role) {
                DB::connection('mis')
                    ->table('x_UserRole')
                    ->where('UserID', '=', $role['UserID'])
                    ->where('RoleID', '=', $role['RoleID'])
                    ->delete();
            }
        }

        $hasInsert = DB::connection('mis')
            ->table('x_UserRole')
            ->insert($addRoles);
    }

    public function changePassword(int $userId, Request $request)
    {
        $userRaw = DB::connection('mis')
            ->table('hlt_LPUDoctor')
            ->select(
                [
                    'hlt_LPUDoctor.LPUDoctorID', 'hlt_LPUDoctor.PCOD'
                ]
            )
            ->where('LPUDoctorID', '=', $userId)
            ->first();

        $xUser = $this->getXUser((int) $userRaw->LPUDoctorID, $userRaw->PCOD);

        $passwordChangedCount = MisPasswordHistory::where('user_id', $xUser->UserID)->count();

        if ($passwordChangedCount > 0) {
            $lastPassword = MisPasswordHistory::where('user_id', $xUser->UserID)->first();
            if ($lastPassword) {
                $hasUpdate = DB::connection('mis')
                    ->table('x_User')
                    ->where('UserID', '=', $xUser->UserID)
                    ->update([
                        'GeneralPassword' => $lastPassword->original_password,
                    ]);

                if ($hasUpdate) {
                    $lastPassword->delete();
                }
            }
        } else {
            $newPassword = $this->computeHash('1234567', $xUser->GUID);
            $passwordHistory = MisPasswordHistory::create(
                [
                    'user_id' => $xUser->UserID,
                    'original_password' => $xUser->GeneralPassword,
                    'password' => $newPassword,
                    'guid' => $xUser->GUID
                ]
            );
            if ($passwordHistory) {
                DB::connection('mis')
                    ->table('x_User')
                    ->where('UserID', '=', $xUser->UserID)
                    ->update([
                        'GeneralPassword' => $newPassword,
                    ]);
            }
        }
    }

    public function roles()
    {
        $templates = MisRoleTemplate::with(['createUser'])->get();
        $misRolesCache = Cache::get('mis_roles', [])->map(function ($item) {
            return [
                'RoleID' => (int)$item->RoleID,
                'Name' => $item->Name
            ];
        });

        if (!empty($misRolesCache)) {
            $templates = $templates->map(function ($template) use ($misRolesCache) {
                $roles = collect($template->roles)->map(function ($role) use ($misRolesCache) {
                    $matchingRole = $misRolesCache->firstWhere('RoleID', (string)$role);

                    return [
                        'RoleID' => $role,
                        'Name' => $matchingRole ? $matchingRole['Name'] : '',
                    ];
                });

                return [
                    ...$template->toArray(),
                    'roles' => $roles
                ];
            });
        }

        return Inertia::render('MIS/Roles/Index', [
            'templates' => $templates,
            'roles' => $misRolesCache
        ]);
    }

    public function createTemplate(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string'],
            'roles' => ['required', 'array'],
        ]);

        $userId = $request->user()->id;

        MisRoleTemplate::create([
            'name' => $data['name'],
            'roles' => $data['roles'],
            'create_user_id' => $userId
        ]);

        return redirect(route('mis.templates.roles'));
    }

    public function updateTemplate(MisRoleTemplate $template, Request $request)
    {
        $data = $request->validate([
            'roles' => ['required', 'array'],
        ]);

        $template->update($data);

        return redirect(route('mis.templates.roles'));
    }

    public function importDoctors(Request $request)
    {
        $file = $request->file('file');

        MisImport::doctors($file);
    }

    private function getRoles() : array|\Illuminate\Contracts\Pagination\CursorPaginator|\Illuminate\Contracts\Pagination\Paginator|\Illuminate\Pagination\AbstractCursorPaginator|\Illuminate\Pagination\AbstractPaginator|Collection|\Illuminate\Support\Enumerable|\Illuminate\Support\LazyCollection|\Spatie\LaravelData\CursorPaginatedDataCollection|\Spatie\LaravelData\DataCollection|\Spatie\LaravelData\PaginatedDataCollection
    {
        // Кеширование тип отделений (актуальность 7 сутки, устаревание 10 суток)
        $roles = Cache::flexible('mis_roles', [604800000, 864000000], function() {
            return DB::connection('mis')
                ->table('x_Role')
                ->select(
                    [
                        'x_Role.RoleID', 'x_Role.Name', 'x_Role.GUID',
                    ]
                )
                ->where('x_Role.RoleID', '>', 0)
                ->orderBy('x_Role.Name')
                ->get();
        });

        $roles = XRole::collect($roles);

        return $roles;
    }
    private function getRolesByUserId(int $userId) : Collection
    {
        $userRoles = DB::connection('mis')
            ->table('x_UserRole')
            ->select(
                [
                    'x_UserRole.UserRoleID', 'x_UserRole.UserID', 'x_UserRole.RoleID',
                ]
            )
            ->where('x_UserRole.UserID', '=', $userId)
            ->where('x_UserRole.UserRoleID', '>', 0)
            ->orderBy('x_UserRole.RoleID')
            ->get();

        $userRoles = XUserRole::collect($userRoles);

        return $userRoles;
    }
    private function getXUser(int $lpuDoctorId, string|null $pcod = null)
    {
        if (empty($lpuDoctorId))
            throw new \Error('Не указан PCOD');

        $storedUserID = MisLPUDoctorToUserID::where('lpu_doctor_id', $lpuDoctorId)->first();
        if ($storedUserID) {
            $xUser = DB::connection('mis')
                ->table('x_User')
                ->select(['UserID', 'GeneralLogin', 'GeneralPassword', 'GUID'])
                ->where('x_User.UserID', '=', $storedUserID->user_id)
                ->first();
        } else {
            if (empty($pcod))
                throw new \Error('Не указан PCOD');

            $xUser = DB::connection('mis')
                ->table('x_User')
                ->select(['UserID', 'GeneralLogin', 'GeneralPassword', 'GUID'])
                ->join('x_UserSettings', 'x_UserSettings.rf_UserID', '=', 'x_User.UserID')
                ->where('x_UserSettings.Property', '=', 'Код врача')
                ->where('x_UserSettings.ValueStr', '=', $pcod)
                ->first();

            if (empty($xUser)) $xUser = null;
            else {
                MisLPUDoctorToUserID::updateOrCreate(['user_id' => $xUser->UserID], [
                    'user_id' => $xUser->UserID,
                    'lpu_doctor_id' => $lpuDoctorId
                ]);
            }
        }

        return $xUser;
    }

    private function formatedLogin($fam, $ot, $im) : string
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

    private function formattedLpus()
    {
        return MisClassifier::getLpu()->map(function (ClassifierLPUData $item) {
            return [
                'value' => $item->id,
                'label' => "$item->code - $item->name",
            ];
        });
    }

    private function formattedDepartments()
    {
        return MisClassifier::getDepartment()->map(function (ClassifierDepartmentData $item) {
            return [
                'value' => $item->id,
                'label' => "$item->code - $item->name",
                'type_id' => $item->type_id,
                'profile_id' => $item->profile_id,
            ];
        });
    }

    private function formattedPrvd()
    {
        return MisClassifier::getPrvd()->map(function (ClassifierPrvdData $item) {
            return [
                'value' => $item->id,
                'label' => "$item->code - $item->name",
                'c_prvd' => $item->code,
                'name' => $item->name
            ];
        });
    }

    private function formattedPrvs()
    {
        return MisClassifier::getPrvs()->map(function (ClassifierPrvsData $item) {
            return [
                'value' => $item->id,
                'label' => "$item->code - $item->name",
                'c_prvs' => $item->code,
            ];
        });
    }

    private function formattedDepartmentProfile()
    {
        return MisClassifier::getDepartmentProfile()->map(function (ClassifierDepartmentProfileData $item) {
            return [
                'value' => $item->id,
                'label' => "$item->code - $item->name",
            ];
        });
    }

    private function formattedDepartmentType()
    {
        return MisClassifier::getDepartmentType()->map(function (ClassifierDepartmentTypeData $item) {
            return [
                'value' => $item->id,
                'label' => "$item->code - $item->name",
            ];
        });
    }
}
