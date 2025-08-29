<?php

namespace App\Jobs;

use App\Data\Mis\DoctorData;
use App\Data\Mis\Import\DoctorImportData;
use App\Data\Mis\InsertLPUDoctorData;
use App\Facades\MisClassifier;
use App\Facades\MisDoctor;
use App\Facades\MisXUser;
use App\Models\MisRoleTemplate;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Npub\Gos\Snils;

class MisCreateImportedAccountJob implements ShouldQueue
{
    use Queueable, Batchable;

    protected DoctorImportData $doctorData;

    /**
     * Create a new job instance.
     */
    public function __construct(DoctorImportData $data)
    {
        $this->doctorData = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $snils = Snils::createFromFormat($this->doctorData->snils);

        // Если указан неверный снилс
        if (Snils::validate($snils->getCanonical(), Snils::FORMAT_CANONICAL) === null) {
            return;
        }

        $generalLogin = MisXUser::formatedLogin($this->doctorData->last_name, $this->doctorData->middle_name, $this->doctorData->first_name);
        $fio = $this->doctorData->last_name . ' ' . $this->doctorData->first_name . ' ' . $this->doctorData->middle_name;

        $misUser = MisXUser::createUser([
            'GeneralLogin' => $generalLogin,
            'FIO' => $fio
        ]);

        if (empty($misUser)) {
            \Log::error("Ошибка при создании учетной записи $generalLogin");
        }

        $lpuCode = $this->doctorData->lpu_code;
        $departmentCode = $this->doctorData->department_code;
        $prvdCode = $this->doctorData->prvd;
        $prvsCode = $this->doctorData->prvs;
        $templateRoleId = $this->doctorData->template_role_id;

        $lpuId = MisClassifier::getLpu(mcod: $lpuCode)->id;
        $departmentId = MisClassifier::getDepartment(code: $departmentCode)->id;
        $prvdId = MisClassifier::getPrvd(code: $prvdCode)->id;
        $prvsId = MisClassifier::getPrvs(code: $prvsCode)->id;

        $roles = MisRoleTemplate::whereId($templateRoleId)->first() ?? [];

        $importDoctorData = DoctorData::from([
            'code' => Str::substr($snils->getCanonical(), -6),
            'first_name' => $this->doctorData->first_name,
            'middle_name' => $this->doctorData->middle_name,
            'last_name' => $this->doctorData->last_name,
            'birth_at' => $this->doctorData->brith_at,
            'snils' => $snils,
            'has_doctor' => 0,
            'has_time' => 0,
            'has_dismissal' => 0,
            'has_special' => 0,
            'rate' => 1.00,
            'lpu_id' => $lpuId,
            'department_id' => $departmentId,
            'prvd_id' => $prvdId,
            'prvs_id' => $prvsId,
            'guid' => Str::uuid()
        ]);

        $doctor = MisDoctor::createDoctor($importDoctorData, true);
    }
}
