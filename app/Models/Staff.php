<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Laravel\Scout\Searchable;

class Staff extends Model
{
    use HasFactory, Searchable;

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

    public function makeSearchableUsing(Collection $models): Collection
    {
        return $models->load('certification');
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => (int) $this->id,
            'full_name' => $this->full_name,
            'job_title' => $this->job_title,
            'inn' => $this->inn,
            'snils' => $this->snils,
            'is_valid' => (int) $this->certification->is_valid,
            'is_request_new' => (int) $this->certification->is_request_new,
            'cert_updated_at' => $this->certification->updated_at,
        ];
    }
}
