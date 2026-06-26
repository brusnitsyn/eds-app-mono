<?php

namespace App\Actions\Eds;

use App\Facades\Audit;
use App\Models\Staff;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class CreateNewStaff
{
    public function create(array $data)
    {
        // Проверка открытого и закрытого ключа на актуальность
        $now = Carbon::now();
        $validTo = Carbon::createFromTimestampMs($data['certificate']['valid_to']);
        $closeKeyValidTo = Carbon::createFromTimestampMs($data['certificate']['close_key_valid_to']);

        if ($validTo->isFuture()) {
            if ($now->diffInMonths($validTo) < 1) {
                $data['certificate']['is_request_new'] = true;
            } else {
                $data['certificate']['is_request_new'] = false;
            }
            $data['certificate']['is_valid'] = true;
        } else {
            $data['certificate']['is_valid'] = false;
        }

        if ($closeKeyValidTo->isFuture()) {
            $data['certificate']['close_key_is_valid'] = true;
        } else {
            $data['certificate']['close_key_is_valid'] = false;
        }

        $certification = $data['certificate'];

        $data['gender'] = 'slava';

        // inn зашифрован недетерминированным AES, поэтому updateOrCreate(['inn' => ...])
        // не нашёл бы существующую запись — ищем по детерминированному блайнд-индексу.
        $staff = Staff::findByInn($data['inn']) ?? new Staff();
        $wasExisting = $staff->exists;
        $staff->fill($data);
        $staff->save();
        if ($staff->certification()->exists()) $staff->certification->delete();
        $staff->certification()->create($certification);

        Audit::log(
            eventType: $wasExisting ? 'staff.updated' : 'staff.created',
            action: $wasExisting ? 'update' : 'create',
            resource: 'Staff:'.$staff->id,
        );

        // Синхронизация поискового индекса (Typesense) — вспомогательная и не
        // должна откатывать сохранение сотрудника/сертификата (этот вызов
        // выполняется внутри DB::transaction в ProcessCertificateUpload), если
        // Typesense временно недоступен.
        try {
            $staff->searchable();
        } catch (\Throwable $e) {
            Log::warning('CreateNewStaff: не удалось синхронизировать поисковый индекс — ' . $e->getMessage());
        }

        return $staff;
    }
}
