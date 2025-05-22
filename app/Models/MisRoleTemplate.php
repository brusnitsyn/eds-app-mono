<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Illuminate\Database\Eloquent\Model;

class MisRoleTemplate extends Model
{
    protected $fillable = [
        'name',
        'roles',
        'create_user_id'
    ];

    protected $casts = [
        'roles' => 'array',
    ];

    public function createUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'create_user_id');
    }
}
