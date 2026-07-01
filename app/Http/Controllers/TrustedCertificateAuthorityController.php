<?php

namespace App\Http\Controllers;

use App\Models\TrustedCertificateAuthority;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TrustedCertificateAuthorityController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:root,intermediate',
            'file' => 'required|file|max:512',
        ]);

        $rawContents = file_get_contents($request->file('file')->getRealPath());
        $parsedCert = @openssl_x509_parse($this->toPem($rawContents));

        if ($parsedCert === false) {
            return back()->withErrors(['file' => 'Не удалось разобрать файл — это не корректный X.509 сертификат']);
        }

        $fileName = Str::random(24) . '.cer';
        Storage::disk('trusted_ca')->put($fileName, $this->toDer($rawContents));

        TrustedCertificateAuthority::create([
            'name' => $request->input('name'),
            'type' => $request->input('type'),
            'file_path' => $fileName,
            'subject_cn' => $parsedCert['subject']['CN'] ?? null,
            'serial_number' => $parsedCert['serialNumberHex'] ?? null,
            'valid_to' => isset($parsedCert['validTo_time_t']) ? $parsedCert['validTo_time_t'] * 1000 : null,
        ]);

        return back();
    }

    public function destroy(TrustedCertificateAuthority $trustedCertificateAuthority)
    {
        Storage::disk('trusted_ca')->delete($trustedCertificateAuthority->file_path);
        $trustedCertificateAuthority->delete();

        return back();
    }

    /**
     * Сырой сертификат в base64 (без PEM-заголовков) для установки в хранилище
     * Windows через CAdESCOM.Certificate.Import на стороне клиента.
     */
    public function content(TrustedCertificateAuthority $trustedCertificateAuthority)
    {
        if (! Storage::disk('trusted_ca')->exists($trustedCertificateAuthority->file_path)) {
            return response()->json(['error' => 'Файл сертификата не найден на сервере'], 404);
        }

        $der = Storage::disk('trusted_ca')->get($trustedCertificateAuthority->file_path);

        return response()->json([
            'name'    => $trustedCertificateAuthority->name,
            'type'    => $trustedCertificateAuthority->type,
            'content' => base64_encode($der),
        ]);
    }

    private function toPem(string $contents): string
    {
        if (str_contains($contents, '-----BEGIN CERTIFICATE-----')) {
            return $contents;
        }

        return "-----BEGIN CERTIFICATE-----\n" . chunk_split(base64_encode($contents), 64, "\n") . "-----END CERTIFICATE-----\n";
    }

    private function toDer(string $contents): string
    {
        if (!str_contains($contents, '-----BEGIN CERTIFICATE-----')) {
            return $contents;
        }

        preg_match('/-----BEGIN CERTIFICATE-----([^-]+)-----END CERTIFICATE-----/s', $contents, $m);
        return base64_decode(preg_replace('/\s+/', '', $m[1] ?? ''));
    }
}
