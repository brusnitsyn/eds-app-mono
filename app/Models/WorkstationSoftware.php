<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkstationSoftware extends Model
{
    protected $table = 'workstation_software';

    protected $fillable = ['key', 'label', 'version', 'file_path', 'original_name', 'file_size'];

    public static array $keys = [
        'chromium_gost'    => 'Chromium GOST',
        'cryptopro_plugin' => 'КриптоПро ЭЦП Browser Plug-in',
        'cryptopro_csp'    => 'КриптоПро CSP',
    ];
}
