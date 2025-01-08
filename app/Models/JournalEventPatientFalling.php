<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalEventPatientFalling extends Model
{
    protected $fillable = [
        'event_at',
        'division_id',
        'journal_type_id',
        'full_name_patient',
        'reason_event',
        'place_event',
        'has_helping',
        'consequences',
    ];
}
