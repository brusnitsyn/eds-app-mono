<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Rules\PasswordNotReused;
use App\Services\Auth\PasswordManager;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and reset the user's forgotten password.
     *
     * @param  array<string, string>  $input
     */
    public function reset(User $user, array $input): void
    {
        Validator::make($input, [
            'password' => [...$this->passwordRules(), new PasswordNotReused($user)],
        ])->validate();

        app(PasswordManager::class)->change($user, $input['password']);
    }
}
