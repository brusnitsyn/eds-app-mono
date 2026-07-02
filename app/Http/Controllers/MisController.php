<?php

namespace App\Http\Controllers;

use App\Data\Mis\Classifier\ClassifierDepartmentData;
use App\Data\Mis\Classifier\ClassifierDepartmentProfileData;
use App\Data\Mis\Classifier\ClassifierDepartmentTypeData;
use App\Data\Mis\Classifier\ClassifierLpuData;
use App\Data\Mis\Classifier\ClassifierPrvdData;
use App\Data\Mis\Classifier\ClassifierPrvsData;
use App\Data\Mis\DoctorData;
use App\Data\Mis\PrvdData;
use App\Facades\MisClassifier;
use App\Facades\MisDoctor;
use App\Facades\MisImport;
use App\Facades\MisXRole;
use App\Facades\MisXUser;
use App\Models\MisRoleTemplate;
use App\Models\Staff;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Spatie\LaravelData\Optional;

class MisController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('MIS/Index', [
            'usersCount' => MisDoctor::countDoctors(null),
        ]);
    }

    public function users(Request $request)
    {
        $searchValue = $request->query('search_value');
        $pageSize = $request->query('page_size', 25);

        return Inertia::render('MIS/Users/Index', [
            'users'       => MisDoctor::getPaginate($searchValue, $pageSize),
            'lpus'        => $this->formattedLpus(),
            'departments' => $this->formattedDepartments(),
            'prvd'        => $this->formattedPrvd(),
            'prvs'        => $this->formattedPrvs(),
        ]);
    }

    public function user(int $userId, Request $request)
    {
        return Inertia::render('MIS/Users/Show', $this->buildUserDetail($userId));
    }

    public function userDetail(int $userId, Request $request)
    {
        return response()->json($this->buildUserDetail($userId));
    }

    private function buildUserDetail(int $userId): array
    {
        $user    = MisDoctor::getDoctorById($userId)->toOriginal();
        $xUser   = MisXUser::getUserByDoctorId($userId);
        $prvds   = MisDoctor::getPrvd($userId);
        $snils   = Str::replace(['-', ' '], '', $user['snils']);
        $staff   = Staff::findBySnils($snils);
        $templates = MisRoleTemplate::with(['createUser'])->get();

        return [
            'user'               => $user,
            'x_user'             => $xUser,
            'jobs'               => $prvds,
            'staff'              => $staff,
            'departments'        => $this->formattedDepartments(),
            'prvd'               => $this->formattedPrvd(),
            'prvs'               => $this->formattedPrvs(),
            'lpus'               => $this->formattedLpus(),
            'department_types'   => $this->formattedDepartmentType(),
            'department_profiles'=> $this->formattedDepartmentProfile(),
            'roles'              => MisXRole::getRoles(),
            'user_roles'         => $xUser ? MisXRole::getRolesByUserId($xUser->UserID) : [],
            'role_templates'     => $templates,
        ];
    }

    /**
     * @throws \Throwable
     */
    public function createUser(Request $request)
    {
        $data = DoctorData::factory()->withoutOptionalValues()->from([
            ...$request->all(),
            'start_at' => Optional::create(),
            'end_at'   => Optional::create(),
            'guid'     => Str::uuid(),
        ]);

        $xUserData = [
            'GeneralLogin' => MisXUser::formatedLogin($data->last_name, $data->middle_name, $data->first_name),
            'AuthMode'     => 1,
            'FIO'          => "$data->last_name $data->first_name $data->middle_name",
        ];

        try {
            $user   = MisXUser::createUser($xUserData);
            $doctor = MisDoctor::createDoctor($data, true);
            MisXUser::assignToDoctor($user, $doctor->code);
        } catch (\Exception $ex) {
            Log::error('Ошибка при создании учетной записи: ' . $ex->getMessage());
        }

        return redirect(route('mis.users'));
    }

    /**
     * @throws \Throwable
     */
    public function createPost(int $userId, Request $request)
    {
        $data = PrvdData::from([
            ...$request->all(),
            'guid'     => Str::uuid(),
            'start_at' => CarbonImmutable::now()->format('Y-m-d H:i:s.u'),
            'end_at'   => CarbonImmutable::create(2222, 01, 01)->format('Y-m-d H:i:s.u'),
        ]);

        MisDoctor::createPrvd($data);

        return back();
    }

    public function updateUser(int $userId, Request $request)
    {
        MisDoctor::updateDoctor($userId, DoctorData::from($request->all()));

        return back();
    }

    public function updateOrCreateAccess(int $doctorId, Request $request)
    {
        $data = $request->validate([
            'GeneralLogin'    => ['required', 'string'],
            'GeneralPassword' => ['required', 'string'],
        ]);

        $xUser = MisXUser::getUserByDoctorId($doctorId);

        if (!$xUser) {
            return back()->withErrors(['message' => 'Пользователь МИС не найден']);
        }

        MisXUser::updateCredentials($xUser->UserID, $data['GeneralLogin'], $data['GeneralPassword']);

        return back();
    }

    public function updatePost(int $doctorId, Request $request)
    {
        MisDoctor::updatePrvd(PrvdData::from($request->all()));

        return back();
    }

    public function updateRoles(int $userId, Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'numeric'],
            'roles'   => ['required', 'array'],
        ]);

        MisXRole::syncRoles((int) $data['user_id'], $data['roles']);

        return back();
    }

    public function changePassword(int $userId, Request $request)
    {
        $xUser = MisXUser::getUserByDoctorId($userId);

        if (!$xUser) {
            return back()->withErrors(['message' => 'Пользователь МИС не найден']);
        }

        MisXUser::changePassword($xUser->UserID);

        return back();
    }

    public function roles()
    {
        $templates = MisRoleTemplate::with(['createUser'])->get();
        $misRoles  = MisXRole::getRoles();

        if ($misRoles->isNotEmpty()) {
            $templates = $templates->map(function ($template) use ($misRoles) {
                $roles = collect($template->roles)->map(function ($role) use ($misRoles) {
                    $match = $misRoles->firstWhere('RoleID', (int) $role);
                    return ['RoleID' => $role, 'Name' => $match['Name'] ?? ''];
                });

                return [...$template->toArray(), 'roles' => $roles];
            });
        }

        return Inertia::render('MIS/Roles/Index', [
            'templates' => $templates,
            'roles'     => $misRoles,
        ]);
    }

    public function createTemplate(Request $request)
    {
        $data = $request->validate([
            'name'  => ['required', 'string'],
            'roles' => ['required', 'array'],
        ]);

        MisRoleTemplate::create([
            'name'           => $data['name'],
            'roles'          => $data['roles'],
            'create_user_id' => $request->user()->id,
        ]);

        return redirect(route('staff'));
    }

    public function updateTemplate(MisRoleTemplate $template, Request $request)
    {
        $data = $request->validate([
            'roles' => ['required', 'array'],
        ]);

        $template->update($data);

        return redirect(route('staff'));
    }

    public function importDoctors(Request $request)
    {
        $file = $request->file('file');

        MisImport::doctors($file);
    }

    private function formattedLpus()
    {
        return MisClassifier::getLpu()->map(function (ClassifierLpuData $item) {
            return ['value' => $item->id, 'label' => "$item->code - $item->name"];
        });
    }

    private function formattedDepartments()
    {
        return MisClassifier::getDepartment()->map(function (ClassifierDepartmentData $item) {
            return [
                'value'      => $item->id,
                'label'      => "$item->code - $item->name",
                'type_id'    => $item->type_id,
                'profile_id' => $item->profile_id,
            ];
        });
    }

    private function formattedPrvd()
    {
        return MisClassifier::getPrvd()->map(function (ClassifierPrvdData $item) {
            return [
                'value'  => $item->id,
                'label'  => "$item->code - $item->name",
                'c_prvd' => $item->code,
                'name'   => $item->name,
            ];
        });
    }

    private function formattedPrvs()
    {
        return MisClassifier::getPrvs()->map(function (ClassifierPrvsData $item) {
            return [
                'value'  => $item->id,
                'label'  => "$item->code - $item->name",
                'c_prvs' => $item->code,
            ];
        });
    }

    private function formattedDepartmentProfile()
    {
        return MisClassifier::getDepartmentProfile()->map(function (ClassifierDepartmentProfileData $item) {
            return ['value' => $item->id, 'label' => "$item->code - $item->name"];
        });
    }

    private function formattedDepartmentType()
    {
        return MisClassifier::getDepartmentType()->map(function (ClassifierDepartmentTypeData $item) {
            return ['value' => $item->id, 'label' => "$item->code - $item->name"];
        });
    }
}
