<?php

namespace App\Rules;

use App\Models\User;
use App\Support\PasswordPolicy;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Hash;

/**
 * Запрет повторного использования последних N паролей (мера ИАФ.3).
 */
class PasswordNotReused implements ValidationRule
{
    public function __construct(private readonly User $user) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Текущий пароль тоже считается «использованным».
        if ($this->user->password && Hash::check($value, $this->user->password)) {
            $fail('Новый пароль не должен совпадать с текущим.');

            return;
        }

        $limit = PasswordPolicy::historyLimit();

        if ($limit <= 0) {
            return;
        }

        $recent = $this->user->passwordHistories()
            ->orderByDesc('created_at')
            ->limit($limit)
            ->pluck('password_hash');

        foreach ($recent as $hash) {
            if (Hash::check($value, $hash)) {
                $fail("Нельзя повторно использовать один из последних {$limit} паролей.");

                return;
            }
        }
    }
}
