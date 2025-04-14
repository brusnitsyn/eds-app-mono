<?php

namespace App\Models;

use App\Traits\MisTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Laravel\Scout\Searchable;

class Staff extends Model
{
    use HasFactory, Searchable, MisTrait;

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
        'mis_user_id',
        'mis_sync_at',
        'mis_login'
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

    /**
     * Переопределение имени индекса модели по умолчанию
     */
    public function searchableAs(): string
    {
        return 'eds_app_staff';
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => (string) $this->id,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'job_title' => $this->job_title,
            'inn' => $this->inn,
            'snils' => $this->snils,
            'created_at' => (int) $this->created_at->timestamp,
        ];
    }
}
