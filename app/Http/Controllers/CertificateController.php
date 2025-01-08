<?php

namespace App\Http\Controllers;

use App\Actions\Eds\ReadCertificate;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function read(Request $request)
    {
        $readCertificate = new ReadCertificate();
        return $readCertificate->read($request->file('certificate'));
    }
}
