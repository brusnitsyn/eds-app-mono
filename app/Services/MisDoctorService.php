<?php

namespace App\Services;

use App\Data\Mis\DoctorData;
use App\Data\Mis\PrvdData;
use App\Facades\MisClassifier;
use App\Models\Mis\DocPrvd;
use App\Models\Mis\LpuDoctor;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MisDoctorService
{
    private const DOCTOR_SELECT = [
        'hlt_LPUDoctor.LPUDoctorID', 'hlt_LPUDoctor.PCOD', 'hlt_LPUDoctor.OT_V', 'hlt_LPUDoctor.IM_V',
        'hlt_LPUDoctor.FAM_V', 'hlt_LPUDoctor.DR', 'hlt_LPUDoctor.SS', 'hlt_LPUDoctor.UGUID',
        'hlt_LPUDoctor.isDoctor', 'hlt_LPUDoctor.inTime', 'hlt_LPUDoctor.isSpecial', 'hlt_LPUDoctor.isDismissal',
        'oms_PRVS.C_PRVS', 'oms_PRVS.PRVS_NAME', 'hlt_LPUDoctor.DateBegin', 'hlt_LPUDoctor.DateEnd',
        'Oms_LPU.M_NAMES', 'oms_Department.DepartmentName',
        'oms_PRVD.NAME', 'hlt_LPUDoctor.rf_PRVSID', 'hlt_LPUDoctor.rf_LPUID',
        'hlt_LPUDoctor.rf_PRVDID', 'hlt_LPUDoctor.rf_DepartmentID',
    ];

    private const PRVD_SELECT = [
        'hlt_DocPRVD.DocPRVDID', 'hlt_DocPRVD.rf_LPUDoctorID',
        'hlt_DocPRVD.D_PRIK', 'hlt_DocPRVD.S_ST', 'hlt_DocPRVD.D_END',
        'hlt_DocPRVD.rf_PRVSID', 'hlt_DocPRVD.rf_HealingRoomID', 'hlt_DocPRVD.rf_DepartmentID',
        'hlt_DocPRVD.MainWorkPlace', 'hlt_DocPRVD.InTime', 'hlt_DocPRVD.GUID',
        'hlt_DocPRVD.rf_PRVDID', 'hlt_DocPRVD.Name', 'hlt_DocPRVD.ShownInSchedule',
        'hlt_DocPRVD.isDismissal', 'hlt_DocPRVD.isSpecial', 'hlt_DocPRVD.PCOD',
        'hlt_DocPRVD.rf_kl_FrmrPrvsID', 'hlt_DocPRVD.rf_kl_DepartmentProfileID',
        'hlt_DocPRVD.rf_kl_DepartmentTypeID',
    ];

    private function withJoins(): \Illuminate\Database\Eloquent\Builder
    {
        return LpuDoctor::join('oms_PRVS', 'hlt_LPUDoctor.rf_PRVSID', '=', 'oms_PRVS.PRVSID')
            ->join('Oms_LPU', 'hlt_LPUDoctor.rf_LPUID', '=', 'Oms_LPU.LPUID')
            ->join('oms_Department', 'hlt_LPUDoctor.rf_DepartmentID', '=', 'oms_Department.DepartmentID')
            ->join('oms_PRVD', 'hlt_LPUDoctor.rf_PRVDID', '=', 'oms_PRVD.PRVDID')
            ->select(self::DOCTOR_SELECT);
    }

    public function getDoctorByPcod(string $code): DoctorData
    {
        $doctor = $this->withJoins()
            ->where('hlt_LPUDoctor.PCOD', $code)
            ->firstOrFail();

        return DoctorData::from($doctor);
    }

    public function getDoctorById(int $id): DoctorData
    {
        $doctor = $this->withJoins()
            ->where('hlt_LPUDoctor.LPUDoctorID', $id)
            ->firstOrFail();

        return DoctorData::from($doctor);
    }

    public function getDoctorByGuid(string $guid): DoctorData
    {
        $doctor = $this->withJoins()
            ->where('hlt_LPUDoctor.UGUID', $guid)
            ->firstOrFail();

        return DoctorData::from($doctor);
    }

    public function getPaginate(string|null $searchValue, int $pageSize): LengthAwarePaginator
    {
        $query = LpuDoctor::select(['LPUDoctorID', 'PCOD', 'OT_V', 'IM_V', 'FAM_V', 'DR', 'SS'])
            ->where('LPUDoctorID', '<>', 0);

        $this->applySearch($query, $searchValue);

        return $query
            ->orderBy('FAM_V')->orderBy('IM_V')->orderBy('OT_V')
            ->paginate($pageSize)
            ->through(fn($d) => [
                'id'          => $d->LPUDoctorID,
                'code'        => $d->PCOD,
                'middle_name' => $d->OT_V,
                'first_name'  => $d->IM_V,
                'last_name'   => $d->FAM_V,
                'brith_at'    => $d->DR,
                'snils'       => $d->SS,
            ]);
    }

    public function countDoctors(string|null $searchValue): int
    {
        $query = LpuDoctor::where('LPUDoctorID', '<>', 0);
        $this->applySearch($query, $searchValue);
        return $query->count();
    }

    public function getSlice(string|null $searchValue, int $offset, int $limit): Collection
    {
        $query = LpuDoctor::leftJoin('oms_PRVD', 'hlt_LPUDoctor.rf_PRVDID', '=', 'oms_PRVD.PRVDID')
            ->leftJoin('oms_Department', 'hlt_LPUDoctor.rf_DepartmentID', '=', 'oms_Department.DepartmentID')
            ->select([
                'hlt_LPUDoctor.LPUDoctorID', 'hlt_LPUDoctor.PCOD',
                'hlt_LPUDoctor.OT_V', 'hlt_LPUDoctor.IM_V', 'hlt_LPUDoctor.FAM_V',
                'hlt_LPUDoctor.DR', 'hlt_LPUDoctor.SS',
                'oms_PRVD.NAME as prvd_name',
                'oms_Department.DepartmentName as department_name',
            ])
            ->where('hlt_LPUDoctor.LPUDoctorID', '<>', 0);

        $this->applySearch($query, $searchValue, 'hlt_LPUDoctor.');

        return $query
            ->orderBy('hlt_LPUDoctor.FAM_V')->orderBy('hlt_LPUDoctor.IM_V')->orderBy('hlt_LPUDoctor.OT_V')
            ->offset($offset)->limit($limit)
            ->get()
            ->map(fn($d) => [
                'id'              => $d->LPUDoctorID,
                'code'            => $d->PCOD,
                'middle_name'     => $d->OT_V,
                'first_name'      => $d->IM_V,
                'last_name'       => $d->FAM_V,
                'brith_at'        => $d->DR,
                'snils'           => $d->SS,
                'prvd_name'       => $d->prvd_name,
                'department_name' => $d->department_name,
            ]);
    }

    public function createDoctor(DoctorData $data, bool $hasCreatePrvd = false): DoctorData
    {
        $guid = $data->guid;

        try {
            DB::connection('mis')->beginTransaction();

            $attrs = $data->except('rate', 'id', 'prvs_code', 'prvs_name', 'lpu_name', 'department_name', 'prvd_name', 'has_password_change')
                ->toArray();
            LpuDoctor::create($attrs);

            $doctor = $this->getDoctorByGuid($guid);

            if ($hasCreatePrvd) {
                $prvdCount = DocPrvd::where('rf_LPUDoctorID', $doctor->id)->count();
                $prvd = MisClassifier::getPrvd()->firstWhere('id', $data->prvd_id);

                $this->createPrvd(PrvdData::from([
                    'doctor_id'       => $doctor->id,
                    'code'            => $doctor->code . '-' . ($prvdCount + 1),
                    'start_at'        => CarbonImmutable::now()->toDateTime(),
                    'resource_type_id'=> 1,
                    'rate'            => 1.00,
                    'in_time'         => 0,
                    'is_special'      => 0,
                    'is_dismissal'    => 0,
                    'shown_in_schedule' => 0,
                    'main_work_place' => 1,
                    'prvs_id'         => $data->prvs_id,
                    'department_id'   => $data->department_id,
                    'prvd_id'         => $data->prvd_id,
                    'guid'            => Str::uuid(),
                    'frmr_prvd_id'    => $prvd->code ?? 0,
                    'name'            => $prvd->name ?? '',
                ]));
            }

            DB::connection('mis')->commit();
            return $doctor;
        } catch (\Exception $e) {
            DB::connection('mis')->rollBack();
            Log::error($e->getMessage());
            throw new \Exception('Ошибка при создании врача');
        }
    }

    public function updateDoctor(int $id, DoctorData $data): void
    {
        LpuDoctor::where('LPUDoctorID', $id)
            ->update(
                $data->except('id', 'start_at', 'end_at', 'prvs_code', 'prvs_name', 'lpu_name', 'department_name', 'prvd_name', 'has_password_change')
                    ->toArray()
            );
    }

    public function getPrvd(int $doctorId): Collection
    {
        return DocPrvd::select(self::PRVD_SELECT)
            ->where('rf_LPUDoctorID', $doctorId)
            ->get()
            ->map(fn($p) => PrvdData::from($p)->toOriginal());
    }

    public function createPrvd(PrvdData $data): PrvdData
    {
        DocPrvd::create($data->except('id')->toArray());
        return $this->getPrvdByGuid($data->guid);
    }

    public function updatePrvd(PrvdData $data): void
    {
        DocPrvd::where('DocPRVDID', $data->id)
            ->update($data->except('id', 'guid')->toArray());
    }

    private function getPrvdByGuid(string $guid): PrvdData
    {
        return PrvdData::from(
            DocPrvd::select(self::PRVD_SELECT)->where('GUID', $guid)->firstOrFail()
        );
    }

    private function applySearch(
        \Illuminate\Database\Eloquent\Builder $query,
        string|null $searchValue,
        string $prefix = ''
    ): void {
        if (empty($searchValue)) {
            return;
        }

        if (intval($searchValue) !== 0) {
            $query->where("{$prefix}PCOD", 'like', "$searchValue%");
        } else {
            $query->whereRaw(
                "CONCAT({$prefix}FAM_V, ' ', {$prefix}IM_V, ' ', {$prefix}OT_V) LIKE ?",
                ["$searchValue%"]
            );
        }
    }
}
