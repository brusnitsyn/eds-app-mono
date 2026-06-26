<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Support\PasswordPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Управление сменой пароля с ведением истории (мера ИАФ.3).
 */
class PasswordManager
{
    /**
     * Установить новый пароль: сохранить старый в историю, обновить метку
     * времени смены и подрезать историю до лимита.
     */
    public function change(User $user, string $newPassword): void
    {
        DB::transaction(function () use ($user, $newPassword): void {
            // Сохраняем текущий пароль в историю до перезаписи.
            if ($user->password) {
                $user->passwordHistories()->create([
                    'password_hash' => $user->password,
                    'created_at' => now(),
                ]);
            }

            $user->forceFill([
                'password' => Hash::make($newPassword),
                'password_changed_at' => now(),
            ])->save();

            $this->trimHistory($user);
        });
    }

    private function trimHistory(User $user): void
    {
        $limit = PasswordPolicy::historyLimit();

        $ids = $user->passwordHistories()
            ->orderByDesc('created_at')
            ->skip($limit)
            ->take(PHP_INT_MAX)
            ->pluck('id');

        if ($ids->isNotEmpty()) {
            $user->passwordHistories()->whereIn('id', $ids)->delete();
        }
    }
}
