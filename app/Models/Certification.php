<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Certification extends Model
{
    use HasFactory;

    protected $fillable = [
        'serial_number',
        'valid_from',
        'valid_to',
        'is_valid',
        'is_request_new',
        'path_certification',
        'file_certification',
        'staff_id'
    ];

    public function staff(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Staff::class);
    }

    public function actual()
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
}
