<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'full_name',
        'job_title',
        'inn',
        'snils',
        'gender',
        'dob',
        'tel',
        'division_id',
    ];

    public function division(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Division::class);
    }

    public function certification(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Certification::class);
    }

    public function integrations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StaffIntegrate::class)->whereNot('deleted_at', '!=');
    }
}
