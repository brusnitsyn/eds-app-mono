<?php

namespace App\Actions\Fortify;

use App\Support\PasswordPolicy;

trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function passwordRules(): array
    {
        return ['required', 'string', PasswordPolicy::rule(), 'confirmed'];
    }
}
