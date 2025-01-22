<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = [
        'name',
        'slug',
    ];

    public function scopes(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(Scope::class, RoleScope::class, 'role_id', 'id', 'id', 'scope_id');
    }
}
