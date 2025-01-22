<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleScope extends Model
{
    protected $fillable = [
        'role_id',
        'scope_id'
    ];

    public function scopes(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Scope::class);
    }

    public function roles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
}
