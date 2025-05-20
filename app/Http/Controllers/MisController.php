<?php

namespace App\Http\Controllers;

use App\Data\Mis\LpuDoctorData;
use App\Data\Mis\XRole;
use App\Data\Mis\XUserRole;
use App\Models\MisLPUDoctorToUserID;
use App\Models\MisPasswordHistory;
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

class MisController extends Controller
{
    public function index(Request $request)
    {
        $usersCount = DB::connection('mis')
            ->table('hlt_LPUDoctor')
            ->select(['LPUDoctorID', 'PCOD', 'OT_V', 'IM_V', 'FAM_V', 'D_SER', 'DR', 'SS'])
            ->where('LPUDoctorID', '<>', 0)
            ->count();

        return Inertia::render('MIS/Index', [
            'usersCount' => $usersCount
        ]);
    }

    public function users(Request $request)
    {
        $pageSize = $request->query('page_size', 25);
        $searchValue = $request->query('search_value');

        $users = DB::connection('mis')
            ->table('hlt_LPUDoctor')
            ->select(['LPUDoctorID', 'PCOD', 'OT_V', 'IM_V', 'FAM_V', 'D_SER', 'DR', 'SS'])
            ->where('LPUDoctorID', '<>', 0)
            ->when($searchValue, function ($query) use ($searchValue) {
                $query->whereRaw("CONCAT(FAM_V, ' ', IM_V, ' ', OT_V) LIKE ?", ["$searchValue%"]);
            })
            ->paginate($pageSize);

        return Inertia::render('MIS/Users/Index',
            [
                'users' => $users,
                'departments' => $this->getDepartments(),
                'prvd' => $this->getPrvd(),
                'prvs' => $this->getPrvs(),
                'lpus' => $this->getLpu(),
            ]);
    }

    public function user(int $userId, Request $request)
    {
        $userRaw = DB::connection('mis')
            ->table('hlt_LPUDoctor')
            ->select(
                [
                    'hlt_LPUDoctor.LPUDoctorID', 'hlt_LPUDoctor.PCOD', 'hlt_LPUDoctor.OT_V', 'hlt_LPUDoctor.IM_V',
                    'hlt_LPUDoctor.FAM_V', 'hlt_LPUDoctor.D_SER', 'hlt_LPUDoctor.DR', 'hlt_LPUDoctor.SS',
                    'hlt_LPUDoctor.isDoctor', 'hlt_LPUDoctor.inTime', 'hlt_LPUDoctor.isSpecial', 'hlt_LPUDoctor.isDismissal',
                    'oms_PRVS.C_PRVS', 'oms_PRVS.PRVS_NAME', 'oms_PRVS.Date_Beg', 'oms_PRVS.Date_End',
                    'oms_LPU.M_NAMES', 'oms_Department.DepartmentName',
                    'oms_PRVD.NAME', 'hlt_LPUDoctor.rf_PRVSID', 'hlt_LPUDoctor.rf_LPUID', 'hlt_LPUDoctor.rf_PRVDID',
                    'hlt_LPUDoctor.rf_DepartmentID'
                ]
            )
            ->join('oms_PRVS', 'hlt_LPUDoctor.rf_PRVSID', '=', 'oms_PRVS.PRVSID')
            ->join('oms_LPU', 'hlt_LPUDoctor.rf_LPUID', '=', 'oms_LPU.LPUID')
            ->join('oms_Department', 'hlt_LPUDoctor.rf_DepartmentID', '=', 'oms_Department.DepartmentID')
            ->join('oms_PRVD', 'hlt_LPUDoctor.rf_PRVDID', '=', 'oms_PRVD.PRVDID')
            ->where('LPUDoctorID', '=', $userId)
            ->first();

        $xUser = $this->getXUser($userRaw->LPUDoctorID, $userRaw->PCOD);

        $hasPasswordChange = MisPasswordHistory::where('user_id', $xUser->UserID)->exists();
        $userRaw->has_password_change = $hasPasswordChange;
        $user = LpuDoctorData::from($userRaw);

        $jobs = DB::connection('mis')
            ->table('hlt_DocPRVD')
            ->select(
                [
                    'hlt_DocPRVD.DocPRVDID', 'hlt_DocPRVD.S_ST', 'hlt_DocPRVD.PCOD', 'oms_PRVD.NAME',
                    'hlt_DocPRVD.isDismissal', 'hlt_DocPRVD.rf_PRVSID', 'hlt_DocPRVD.rf_DepartmentID',
                    'hlt_DocPRVD.rf_PRVDID', 'hlt_DocPRVD.rf_kl_DepartmentProfileID', 'hlt_DocPRVD.rf_kl_DepartmentTypeID',
                    'hlt_DocPRVD.MainWorkPlace', 'hlt_DocPRVD.InTime', 'hlt_DocPRVD.ShownInSchedule', 'hlt_DocPRVD.isSpecial',
                ]
            )
            ->join('oms_PRVD', 'hlt_DocPRVD.rf_PRVDID', '=', 'oms_PRVD.PRVDID')
            ->where('hlt_DocPRVD.rf_LPUDoctorID', '=', $userId)
            ->orderBy('hlt_DocPRVD.PCOD')
            ->get();

        $jobs = $jobs->map(function ($item) {
            return [
                'DocPRVDID' => (float)$item->DocPRVDID,
                'S_ST' => (float)$item->S_ST,
                'PCOD' => $item->PCOD,
                'NAME' => $item->NAME,
                'isDismissal' => (bool)$item->isDismissal,
                'rf_PRVSID' => (int)$item->rf_PRVSID,
                'rf_DepartmentID' => (int)$item->rf_DepartmentID,
                'rf_PRVDID' => (int)$item->rf_PRVDID,
                'rf_kl_DepartmentProfileID' => $item->rf_kl_DepartmentProfileID == '0' ? null : (int)$item->rf_kl_DepartmentProfileID,
                'rf_kl_DepartmentTypeID' => $item->rf_kl_DepartmentTypeID == '0' ? null : (int)$item->rf_kl_DepartmentTypeID,
                'MainWorkPlace' => (bool)$item->MainWorkPlace,
                'InTime' => (bool)$item->InTime,
                'ShownInSchedule' => (bool)$item->ShownInSchedule,
                'isSpecial' => (bool)$item->isSpecial,
            ];
        });

        return Inertia::render('MIS/Users/Show', [
            'user' => $user,
            'x_user' => $xUser,
            'jobs' => $jobs,
            'departments' => $this->getDepartments(),
            'prvd' => $this->getPrvd(),
            'prvs' => $this->getPrvs(),
            'lpus' => $this->getLpu(),
            'department_types' => $this->getDepartmentType(),
            'department_profiles' => $this->getDepartmentProfiles(),
            'roles' => $this->getRoles(),
            'user_roles' => $this->getRolesByUserId($xUser->UserID)
        ]);
    }

    public function createUser(Request $request)
    {
        $data = $request->validate([
            'PCOD' => ['required', 'string'],
            'OT_V' => ['required', 'string'],
            'IM_V' => ['required', 'string'],
            'FAM_V' => ['required', 'string'],
            'DR' => ['required', 'string'],
            'SS' => ['required', 'string'],
            'isDoctor' => ['required', 'boolean'],
            'inTime' => ['required', 'boolean'],
            'isSpecial' => ['required', 'boolean'],
            'isDismissal' => ['required', 'boolean'],
            'S_ST' => ['required', 'numeric'],
            'rf_LPUID' => ['required', 'numeric'],
            'rf_PRVSID' => ['required', 'numeric'],
            'rf_DepartmentID' => ['required', 'numeric'],
            'rf_PRVDID' => ['required', 'numeric'],
        ]);

        $data['DR'] = Carbon::parse($data['DR'])->toDateTimeLocalString();

        DB::connection('mis')->beginTransaction();

        try {
            DB::connection('mis')
                ->table('hlt_LPUDoctor')
                ->insert(Arr::except($data, ['S_ST']));

            $lpuDoctorId = DB::connection('mis')
                ->selectOne("SELECT IDENT_CURRENT('hlt_LPUDoctor') as LPUDoctorID")
                ->LPUDoctorID;

            $dataPRVD = Arr::except($data, ['OT_V', 'IM_V', 'FAM_V', 'DR', 'SS', 'isDoctor', 'rf_LPUID']);
            $dataPRVD['rf_LPUDoctorID'] = $lpuDoctorId;
            $dataPRVD['D_END'] = Carbon::parse('2222-01-01 00:00:00.000')->toDateTimeLocalString();
            $dataPRVD['rf_ResourceTypeID'] = 1;

            $prvd = DB::connection('mis')
                ->table('oms_PRVD')
                ->select([
                    'C_PRVD'
                ])
                ->where('PRVDID', '=', $data['rf_PRVDID'])
                ->first();

            $hasExistFrmrPrvd = DB::connection('mis')
                ->table('oms_kl_FrmrPrvd')
                ->where('Code', $prvd->C_PRVD)
                ->exists();

            $dataPRVD['rf_kl_FrmrPrvdID'] = $hasExistFrmrPrvd ? $prvd->C_PRVD : 0;

            DB::connection('mis')
                ->table('hlt_DocPRVD')
                ->insert($dataPRVD);

            $login = $this->formatedLogin($data['FAM_V'], $data['OT_V'], $data['IM_V']);

            $xUser = [
                'GeneralLogin' => $login,
                'AuthMode' => 1,
                'FIO' => "{$data['FAM_V']} {$data['IM_V']} {$data['OT_V']}",
                'GeneralPassword' => ''
            ];

            DB::connection('mis')
                ->table('x_User')
                ->insert($xUser);

            $createdXUser = DB::connection('mis')
                ->table('x_User')
                ->select(['UserID', 'GUID'])
                ->where('GeneralLogin', '=', $login)
                ->first();

            $xUser['GeneralPassword'] = $this->computeHash('1234567', $createdXUser->GUID);

            $updatedXUser = DB::connection('mis')
                ->table('x_User')
                ->where('UserID', '=', $createdXUser->UserID)
                ->update($xUser);

//            select * from x_UserSettings
//            where Property = 'Код врача' and ValueStr = '246515'
            $settingPcod = [
                'rf_UserID' => (int)$createdXUser->UserID,
                'Property' => 'Код врача',
                'DocTypeDefGUID' => Uuid::NIL,
                'ValueStr' => $data['PCOD'],
                'rf_SettingTypeID' => 7,
                'OwnerGUID' => $createdXUser->GUID
            ];

            DB::connection('mis')
                ->table('x_UserSettings')
                ->insert($settingPcod);

            DB::connection('mis')->commit();

        } catch (\Exception $e) {
            DB::connection('mis')->rollBack();
            throw $e;
        }

        return redirect(route('mis.users'));
    }

    public function createPost(int $userId, Request $request)
    {
        $data = $request->validate([
            'PCOD' => ['required', 'string'],
            'MainWorkPlace' => ['required', 'boolean'],
            'InTime' => ['required', 'boolean'],
            'ShownInSchedule' => ['required', 'boolean'],
            'isSpecial' => ['required', 'boolean'],
            'isDismissal' => ['required', 'boolean'],
            'S_ST' => ['required', 'numeric'],
            'rf_DepartmentID' => ['required', 'numeric'],
            'rf_PRVDID' => ['required', 'numeric'],
            'rf_PRVSID' => ['required', 'numeric'],
            'rf_kl_DepartmentProfileID' => ['required', 'numeric'],
            'rf_kl_DepartmentTypeID' => ['required', 'numeric'],
        ]);

        $data['rf_LPUDoctorID'] = $userId;
        $data['rf_ResourceTypeID'] = 1;

        DB::connection('mis')->beginTransaction();

        try {
            DB::connection('mis')
                ->table('hlt_DocPRVD')
                ->insert($data);

            DB::connection('mis')->commit();

        } catch (\Exception $e) {
            DB::connection('mis')->rollBack();
            throw $e;
        }

        return redirect(route('mis.user', ['userId' => $userId]));
    }

    public function updateUser(int $doctorId, Request $request)
    {
        $data = $request->validate([
            'PCOD' => ['required', 'string'],
            'OT_V' => ['required', 'string'],
            'IM_V' => ['required', 'string'],
            'FAM_V' => ['required', 'string'],
            'DR' => ['required', 'string'],
            'SS' => ['required', 'string'],
            'isDoctor' => ['required', 'boolean'],
            'inTime' => ['required', 'boolean'],
            'isSpecial' => ['required', 'boolean'],
            'isDismissal' => ['required', 'boolean'],
            'rf_LPUID' => ['required', 'numeric'],
            'rf_PRVSID' => ['required', 'numeric'],
            'rf_DepartmentID' => ['required', 'numeric'],
            'rf_PRVDID' => ['required', 'numeric'],
        ]);
        $data['DR'] = Carbon::parse($data['DR'])->toDateTimeLocalString();

        $hasUpdated = DB::connection('mis')
            ->table('hlt_LPUDoctor')
            ->where('LPUDoctorID', '=', $doctorId)
            ->update($data);

        return redirect(route('mis.user', ['userId' => $doctorId]));
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
            ->where('Property', 'like', 'Код врача')
            ->where('ValueStr', '=', $doctor->PCOD)
            ->first();

        $userId = (int)$params->rf_UserID;

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
        $data = $request->validate([
            'PCOD' => ['required', 'string'],
            'MainWorkPlace' => ['required', 'boolean'],
            'InTime' => ['required', 'boolean'],
            'ShownInSchedule' => ['required', 'boolean'],
            'isSpecial' => ['required', 'boolean'],
            'isDismissal' => ['required', 'boolean'],
            'S_ST' => ['required', 'numeric'],
            'rf_DepartmentID' => ['required', 'numeric'],
            'rf_PRVDID' => ['required', 'numeric'],
            'rf_PRVSID' => ['nullable', 'numeric'],
            'rf_kl_DepartmentProfileID' => ['numeric'],
            'rf_kl_DepartmentTypeID' => ['numeric'],
        ]);

        $prvdId = $request->input('DocPRVDID');
        $prvd = DB::connection('mis')
            ->table('hlt_DocPRVD')
            ->where('DocPRVDID', '=', $prvdId)
            ->update($data);

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

    private function getDepartments() : Collection
    {
        // Кеширование отделений (актуальность 1 сутки, устаревание 2 суток)
        $departments = Cache::flexible('mis_departments', [86400, 172800], function() {
            return DB::connection('mis')
                ->table('oms_Department')
                ->select(
                    [
                        'oms_Department.DepartmentID', 'oms_Department.DepartmentCODE', 'oms_Department.DepartmentNAME',
                        'oms_Department.rf_kl_DepartmentTypeID', 'oms_Department.rf_kl_DepartmentProfileID'
                    ]
                )
                ->join('oms_LPU', 'oms_Department.rf_LPUID', '=', 'oms_LPU.LPUID')
                ->where('oms_Department.DepartmentID', '>', 0)
                ->where('oms_LPU.rf_MainLPUID', '=', 844)
                ->whereDate('oms_Department.Date_E', '>', Carbon::now()->toDateString())
                ->orderBy('oms_Department.DepartmentID')
                ->get();
        });

        $departments = $departments->map(function ($item) {
            return [
                'value' => intval($item->DepartmentID),
                'label' => "$item->DepartmentCODE - $item->DepartmentNAME",
                'type_id' => intval($item->rf_kl_DepartmentTypeID),
                'profile_id' => intval($item->rf_kl_DepartmentProfileID),
            ];
        });

        return $departments;
    }
    private function getPrvd() : Collection
    {
        // Кеширование должностей (актуальность 1 сутки, устаревание 2 суток)
        $prvd = Cache::flexible('mis_prvd', [86400, 172800], function() {
            return DB::connection('mis')
                ->table('oms_PRVD')
                ->select(
                    [
                        'oms_PRVD.PRVDID', 'oms_PRVD.C_PRVD', 'oms_PRVD.NAME',
                    ]
                )
                ->where('oms_PRVD.PRVDID', '>', 0)
                ->whereDate('oms_PRVD.Date_E', '>', Carbon::now()->toDateString())
                ->orderBy('oms_PRVD.PRVDID')
                ->get();
        });

        $prvd = $prvd->map(function ($item) {
            return [
                'value' => intval($item->PRVDID),
                'label' => "$item->C_PRVD - $item->NAME",
                'c_prvd' => $item->C_PRVD,
            ];
        });

        return $prvd;
    }
    private function getPrvs() : Collection
    {
        // Кеширование специальностей (актуальность 1 сутки, устаревание 2 суток)
        $prvs = Cache::flexible('mis_prvs', [86400, 172800], function() {
            return DB::connection('mis')
                ->table('oms_PRVS')
                ->select(
                    [
                        'oms_PRVS.PRVSID', 'oms_PRVS.C_PRVS', 'oms_PRVS.PRVS_NAME',
                    ]
                )
                ->where('oms_PRVS.PRVSID', '>', 0)
                ->whereDate('oms_PRVS.Date_End', '>', Carbon::now()->toDateString())
                ->orderBy('oms_PRVS.PRVSID')
                ->get();
        });

        $prvs = $prvs->map(function ($item) {
            return [
                'value' => intval($item->PRVSID),
                'label' => "$item->C_PRVS - $item->PRVS_NAME",
                'c_prvs' => $item->C_PRVS,
            ];
        });

        return $prvs;
    }
    private function getLpu() : Collection
    {
        // Кеширование главное лпу (актуальность всегда)
        $mainLpu = Cache::forever('mis_main_lpu', DB::connection('mis')
            ->table('Oms_LPU')
            ->select(
                [
                    'Oms_LPU.LPUID', 'Oms_LPU.MCOD', 'Oms_LPU.M_NAMES',
                ]
            )
            ->where('Oms_LPU.LPUID', '=', 844)
            ->first());

        $mainLpu = Cache::get('mis_main_lpu');

        // Кеширование ЛПУ (актуальность 1 сутки, устаревание 2 суток)
        $lpus = Cache::flexible('mis_lpus', [86400, 172800], function() use ($mainLpu) {
            $lpu = DB::connection('mis')
                ->table('Oms_LPU')
                ->select(
                    [
                        'Oms_LPU.LPUID', 'Oms_LPU.MCOD', 'Oms_LPU.M_NAMES',
                    ]
                )
                ->where('Oms_LPU.LPUID', '>', 0)
                ->where('Oms_LPU.rf_MainLPUID', '=', $mainLpu->LPUID)
                ->whereDate('Oms_LPU.DATE_E', '>', Carbon::now()->toDateString())
                ->orderBy('Oms_LPU.LPUID')
                ->get();

            $lpu->prepend($mainLpu);

            return $lpu;
        });

        $lpus = $lpus->map(function ($item) {
            return [
                'value' => intval($item->LPUID),
                'label' => "$item->MCOD - $item->M_NAMES",
            ];
        });

        return $lpus;
    }
    private function getDepartmentType() : Collection
    {
        // Кеширование тип отделений (актуальность 1 сутки, устаревание 2 суток)
        $departmentTypes = Cache::flexible('mis_department_type', [86400, 172800], function() {
            return DB::connection('mis')
                ->table('oms_kl_DepartmentType')
                ->select(
                    [
                        'oms_kl_DepartmentType.kl_DepartmentTypeID', 'oms_kl_DepartmentType.Code', 'oms_kl_DepartmentType.Name',
                    ]
                )
                ->where('oms_kl_DepartmentType.kl_DepartmentTypeID', '>', 0)
                ->whereDate('oms_kl_DepartmentType.Date_E', '>', Carbon::now()->toDateString())
                ->orderBy('oms_kl_DepartmentType.kl_DepartmentTypeID')
                ->get();
        });

        $departmentTypes = $departmentTypes->map(function ($item) {
            return [
                'value' => intval($item->kl_DepartmentTypeID),
                'label' => "$item->Code - $item->Name",
            ];
        });

        return $departmentTypes;
    }
    private function getDepartmentProfiles() : Collection
    {
        // Кеширование тип отделений (актуальность 1 сутки, устаревание 2 суток)
        $departmentProfiles = Cache::flexible('mis_department_profile', [86400, 172800], function() {
            return DB::connection('mis')
                ->table('oms_kl_DepartmentProfile')
                ->select(
                    [
                        'oms_kl_DepartmentProfile.kl_DepartmentProfileID', 'oms_kl_DepartmentProfile.Code', 'oms_kl_DepartmentProfile.Name',
                    ]
                )
                ->where('oms_kl_DepartmentProfile.kl_DepartmentProfileID', '>', 0)
                ->whereDate('oms_kl_DepartmentProfile.Date_E', '>', Carbon::now()->toDateString())
                ->orderBy('oms_kl_DepartmentProfile.kl_DepartmentProfileID')
                ->get();
        });

        $departmentProfiles = $departmentProfiles->map(function ($item) {
            return [
                'value' => intval($item->kl_DepartmentProfileID),
                'label' => "$item->Code - $item->Name",
            ];
        });

        return $departmentProfiles;
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

            MisLPUDoctorToUserID::updateOrCreate(['user_id' => $xUser->UserID], [
                'user_id' => $xUser->UserID,
                'lpu_doctor_id' => $lpuDoctorId
            ]);
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
}
