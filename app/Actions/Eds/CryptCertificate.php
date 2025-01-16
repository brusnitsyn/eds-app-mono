<?php

namespace App\Actions\Eds;

use Illuminate\Support\Facades\Crypt;

class CryptCertificate
{
    public function __invoke(string $path)
    {
        $path = storage_path($path);
        $fileInfo = pathinfo($path);
        $file = file_get_contents($path);
        $cryptFile = Crypt::encryptString($file);
        $newPath = $fileInfo['dirname'] . '/temp/' . $fileInfo['basename'];
        file_put_contents($newPath, $cryptFile);
    }
}
