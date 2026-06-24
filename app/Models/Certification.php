<?php

namespace App\Models;

use App\Observers\CertificationObserver;
use App\Traits\MisTrait;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

#[ObservedBy(CertificationObserver::class)]
class Certification extends Model
{
    use HasFactory, MisTrait;

    protected $fillable = [
        'serial_number',
        'valid_from',
        'valid_to',
        'is_valid',
        'is_request_new',
        'path_certification',
        'file_certification',
        'staff_id',
        'close_key_valid_to',
        'close_key_is_valid',
        'close_key_is_request_new',
        'revoked_at',

        'mis_serial_number',
        'mis_valid_from',
        'mis_valid_to',
        'mis_is_identical',
    ];

    protected $casts = [
        'revoked_at' => 'datetime',
    ];

    public function staff(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Staff::class, 'id', 'staff_id');
    }

    /**
     * Only the most recently uploaded certificate per employee.
     */
    public function scopeLatestPerStaff($query)
    {
        return $query->whereIn('id', function ($q) {
            $q->selectRaw('MAX(id)')->from('certifications')->groupBy('staff_id');
        });
    }

    public function actual(): array
    {
        $now = Carbon::now();
        $validTo = Carbon::createFromTimestampMs($this->valid_to);

        $arr = [
            'has_valid' => false,
            'has_request_new' => false
        ];

        if ($validTo->isFuture()) {
            if ($now->diffInMonths($validTo) < 1) {
                $arr['has_request_new'] = true;
            }
            $arr['has_valid'] = true;
        }
        return $arr;
    }

    /**
     * @return 'revoked'|'expired'|'expiring'|'valid'
     */
    public function status(): string
    {
        if ($this->revoked_at !== null) {
            return 'revoked';
        }

        $validTo = Carbon::createFromTimestampMs($this->valid_to);

        if (!$this->is_valid || $validTo->isPast()) {
            return 'expired';
        }

        if (Carbon::now()->diffInDays($validTo, false) <= 30) {
            return 'expiring';
        }

        return 'valid';
    }
}
