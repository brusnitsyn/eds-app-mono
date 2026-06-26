<?php

namespace App\Models;

use App\Models\Concerns\HasPdnEncryption;
use App\Traits\MisTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

class Staff extends Model
{
    use HasFactory, Searchable, MisTrait, HasPdnEncryption;

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

    /**
     * Поля ПДн, шифруемые на уровне приложения (мера ЗНИ, ОЦЛ.2).
     *
     * @var array<int, string>
     */
    protected array $encrypted = [
        'snils',
        'inn',
        'first_name',
        'middle_name',
        'last_name',
        'full_name',
        'job_title',
        'mis_login',
    ];

    /**
     * Карта «зашифрованный атрибут → колонка детерминированного хеша для поиска».
     *
     * @var array<string, string>
     */
    protected array $pdnLookupColumns = [
        'snils' => 'snils_hash',
        'inn' => 'inn_hash',
        'job_title' => 'job_title_hash',
    ];

    /**
     * job_title значим посимвольно (дефис/пробел не нормализуются, в отличие
     * от СНИЛС/ИНН) — строгий хеш во избежание ложных совпадений разных
     * должностей, отличающихся только форматированием.
     *
     * @var array<int, string>
     */
    protected array $pdnExactLookupColumns = ['job_title'];

    /**
     * Найти сотрудника по СНИЛС без расшифровки всей таблицы (блайнд-индекс).
     */
    public static function findBySnils(string $snils): ?self
    {
        return static::query()->where('snils_hash', static::pdnLookupHash($snils))->first();
    }

    /**
     * Отфильтровать сотрудников по списку СНИЛС через блайнд-индекс.
     */
    public function scopeBySnilsIn(Builder $query, iterable $snilsList): Builder
    {
        $hashes = collect($snilsList)->filter()->map(fn (string $snils) => static::pdnLookupHash($snils))->values();

        return $query->whereIn('snils_hash', $hashes);
    }

    /**
     * Найти сотрудника по ИНН без расшифровки всей таблицы (блайнд-индекс).
     */
    public static function findByInn(string $inn): ?self
    {
        return static::query()->where('inn_hash', static::pdnLookupHash($inn))->first();
    }

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
        return $models->load(['certification' => function ($query) {
            $query->latest();
        }]);
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
            'first_name' => Str::lower($this->first_name),
            'middle_name' => Str::lower($this->middle_name),
            'last_name' => Str::lower($this->last_name),
            'full_name' => Str::lower($this->full_name),
            'job_title' => Str::lower($this->job_title),
            'certification.valid_to' => $this->certification ? (int) Carbon::createFromTimestampMs($this->certification->latest()->first()?->valid_to)->timestamp : null,
            'certification.close_key_valid_to' => $this->certification ? (int) Carbon::createFromTimestampMs($this->certification->latest()->first()?->close_key_valid_to)->timestamp : null,
            'inn' => $this->inn,
            'snils' => $this->snils,
            'created_at' => (int) $this->created_at->timestamp,
        ];
    }

    /**
     * Контрольная сумма записи для контроля целостности (мера ОЦЛ.2).
     * Считается на уже расшифрованных значениях, поэтому не зависит от
     * выбранного драйвера шифрования.
     */
    public function computePdnChecksum(): string
    {
        $payload = implode('|', [$this->getKey(), $this->snils, $this->inn, $this->full_name, $this->dob]);

        return hash_hmac('sha256', $payload, (string) config('security.encryption.pseudonym_key'));
    }

    /**
     * Не нарушена ли целостность ПДн записи с момента последнего сохранения.
     */
    public function integrityValid(): bool
    {
        return $this->pdn_checksum !== null && hash_equals($this->pdn_checksum, $this->computePdnChecksum());
    }

    protected static function booted(): void
    {
        static::saving(function (Staff $staff) {
            $staff->pdn_checksum = $staff->computePdnChecksum();
        });
    }
}
