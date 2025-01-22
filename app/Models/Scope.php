<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Scope extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function roleScopes(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(RoleScope::class);
    }
}
