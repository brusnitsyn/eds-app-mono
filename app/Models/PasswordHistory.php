<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * История паролей пользователя для запрета повторного использования.
 *
 * Мера ФСТЭК: ИАФ.3 — запрет повтора последних N паролей.
 *
 * @property int $user_id
 * @property string $password_hash
 */
class PasswordHistory extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'password_histories';

    protected $fillable = ['user_id', 'password_hash'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
