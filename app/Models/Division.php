<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    use HasFactory;

    protected $fillable = [
        'label'
    ];

    public function staff(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Staff::class);
    }

    public function journalEventPatientFalling()
    {
        return $this->hasMany(JournalEventPatientFalling::class);
    }
}
