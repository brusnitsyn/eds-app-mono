<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;

class CryptoService
{
    public function encryptFile(string $path)
    {
        $file = file_get_contents($path);
        $cryptFile = Crypt::encryptString($file);
        file_put_contents($path, $cryptFile);
    }

    public function decryptFile(string $path)
    {
        $file = file_get_contents($path);
        $decryptFile = Crypt::decryptString($file);
        file_put_contents($path, $decryptFile);
    }
}
