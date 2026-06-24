<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrustedCertificateAuthority extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'file_path',
        'subject_cn',
        'serial_number',
        'valid_to',
    ];
}
