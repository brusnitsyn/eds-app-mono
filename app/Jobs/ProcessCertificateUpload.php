<?php

namespace App\Jobs;

use App\Actions\Eds\CreateNewStaff;
use App\Events\CertificateProcessingEvent;
use App\Facades\Crypto;
use App\Models\Staff;
use App\Services\PythonCertificateParserService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProcessCertificateUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    /**
     * Детерминированные проверки (целостность/цепочка/CRL) при провале не имеет
     * смысла повторять — повтор даст тот же результат и продублирует broadcast.
     */
    public $tries = 1;

    /**
     * Явный лимит вместо дефолтного воркер-таймаута (60с у queue:listen без
     * --timeout) — проверка CRL ходит в сеть за пределами этого процесса
     * (см. Python certificate_parser.py), и на медленном/недоступном УЦ может
     * не успеть за минуту даже с локальными таймаутами внутри парсера.
     */
    public $timeout = 90;

    protected $extractedCertificatePath;

    /**
     * Этап, который транслировался последним — нужен только для failed(),
     * чтобы корректно подписать событие при сбое, не дошедшем до fail().
     */
    protected string $currentStage = 'container';

    /**
     * true, если для текущего этапа уже отправлено failed-событие через fail()
     * или явный broadcastStage(..., 'failed', ...). Не даёт failed() продублировать
     * то же самое событие.
     */
    protected bool $failureAlreadyBroadcast = false;

    /**
     * Идентификатор сертификата внутри пакета загрузки — отличает события
     * этого job'а от событий других сертификатов того же батча на фронте
     * (см. ReadCertificate, который генерирует его при сборке job'ов).
     */
    public $packageId;

    /**
     * Человекочитаемая подпись сертификата для отображения в списке пакета.
     */
    public $packageLabel;

    public function __construct($extractedCertificatePath, $packageId = null, $packageLabel = null)
    {
        $this->extractedCertificatePath = $extractedCertificatePath;
        $this->packageId = $packageId;
        $this->packageLabel = $packageLabel;
    }

    public function handle(PythonCertificateParserService $pythonCertificateParser): void
    {
        $extractedFiles = Storage::disk('temp')->allFiles($this->extractedCertificatePath);

        // ---- Этап 1: целостность контейнера ----
        $this->broadcastStage('running', 'info', 'Проверка целостности контейнера…', 'container');

        $cerFile = collect($extractedFiles)->first(
            fn ($file) => pathinfo($file, PATHINFO_EXTENSION) === 'cer'
        );
        if (!$cerFile) {
            $this->fail('container', 'В архиве не найден файл сертификата (.cer)');
        }

        $hasKeyContainer = collect($extractedFiles)->contains(
            fn ($file) => basename($file) === 'header.key'
        );
        if (!$hasKeyContainer) {
            $this->fail('container', 'В архиве не найден контейнер закрытого ключа КриптоПро (header.key)');
        }

        $tempCertificatePath = Storage::disk('temp')->path($cerFile);
        $certContents = file_get_contents($tempCertificatePath);
        $certificatePemContent = '-----BEGIN CERTIFICATE-----' . PHP_EOL
            . chunk_split(base64_encode($certContents), 64, PHP_EOL)
            . '-----END CERTIFICATE-----' . PHP_EOL;

        $parsedCert = @openssl_x509_parse($certificatePemContent);
        if ($parsedCert === false) {
            $this->fail('container', 'Не удалось разобрать структуру сертификата (некорректный X.509)');
        }

        try {
            $pythonParsedCert = $pythonCertificateParser->parse($tempCertificatePath);
        } catch (\Throwable $e) {
            $this->fail('container', 'Ошибка Python-парсера сертификатов: ' . $e->getMessage());
        }

        $serialNumber = $pythonParsedCert['serial_number'] ?? null;
        if (!is_string($serialNumber) || $serialNumber === '') {
            $this->fail('container', 'Не удалось получить серийный номер сертификата');
        }

        $this->broadcastStage('done', 'success', 'Целостность контейнера подтверждена', 'container');

        // ---- Этап 2: срок действия сертификата ----
        $this->broadcastStage('running', 'info', 'Проверка срока действия сертификата…', 'expiry');
        $expiry = $this->checkExpiry($parsedCert['validFrom_time_t'] ?? null, $parsedCert['validTo_time_t'] ?? null);
        $this->broadcastStage($expiry['status'], $expiry['type'], $expiry['message'], 'expiry');
        if ($expiry['status'] === 'failed') {
            throw new \RuntimeException($expiry['message']);
        }

        // ---- Этап 3: цепочка доверия ----
        $this->broadcastStage('running', 'info', 'Проверка цепочки доверия…', 'chain');
        $chain = $this->checkTrustChain($parsedCert['issuer']['CN'] ?? null);
        $this->broadcastStage($chain['status'], $chain['type'], $chain['message'], 'chain');
        if ($chain['status'] === 'failed') {
            throw new \RuntimeException($chain['message']);
        }

        // ---- Этап 4: проверка по списку отзыва (CRL) ----
        $this->broadcastStage('running', 'info', 'Проверка по списку отзыва (CRL)…', 'crl');
        $crl = $this->describeRevocation($pythonParsedCert['revocation'] ?? null);
        $this->broadcastStage($crl['status'], $crl['type'], $crl['message'], 'crl');
        if ($crl['status'] === 'failed') {
            throw new \RuntimeException($crl['message']);
        }

        // ---- Сохранение ----
        $validToTimestamp = Carbon::parse($parsedCert['validTo_time_t'])->getTimestampMs();
        $snils = $parsedCert['subject']['SNILS'] ?? null;

        $staff = $snils !== null ? Staff::findBySnils($snils) : null;
        if ($staff && $staff->certification && $staff->certification->valid_to > $validToTimestamp) {
            Log::info('Сертификат устаревший, пропускаем пакет');
            $this->broadcastStage('done', 'success', 'В реестре уже есть более новый сертификат сотрудника', 'save', [
                'full_name' => $staff->full_name,
                'job_title' => $staff->job_title,
                'snils' => $staff->snils,
                'serial_number' => $staff->certification->serial_number,
                'valid_from' => $staff->certification->valid_from,
                'valid_to' => $staff->certification->valid_to,
            ]);

            return;
        }

        $destinationPath = hash('md5', $snils);
        $this->moveDirectoryToStorage($this->extractedCertificatePath, $destinationPath);

        $certificateFile = Storage::disk('certification')->path("$destinationPath/" . basename($cerFile));
        $certificationInfo = $this->buildCertificationInfo($certificateFile, $parsedCert, $serialNumber);
        $certificationInfo['certificate']['path_certification'] = $destinationPath;
        $certificationInfo['certificate']['file_certification'] = basename($cerFile);

        DB::transaction(function () use ($certificationInfo) {
            $createdStaff = new CreateNewStaff();
            $createdStaff->create($certificationInfo);
        });

        $this->encryptFilesInDirectory($destinationPath);
        Storage::disk('temp')->deleteDirectory($this->extractedCertificatePath);

        $this->broadcastStage('done', 'success', "Сертификат сотрудника «{$certificationInfo['full_name']}» сохранён в реестре", 'save', [
            'full_name' => $certificationInfo['full_name'],
            'job_title' => $certificationInfo['job_title'],
            'snils' => $certificationInfo['snils'],
            'serial_number' => $certificationInfo['certificate']['serial_number'],
            'valid_from' => $certificationInfo['certificate']['valid_from'],
            'valid_to' => $certificationInfo['certificate']['valid_to'],
        ]);
    }

    private function broadcastStage(string $status, string $type, string $message, string $stage, array $data = []): void
    {
        $this->currentStage = $stage;
        if ($status === 'failed') {
            $this->failureAlreadyBroadcast = true;
        }

        broadcast(new CertificateProcessingEvent($status, $type, $message, $stage, $data, $this->packageId, $this->packageLabel));
    }

    /**
     * Логирует и транслирует провал этапа, затем прерывает обработку.
     *
     * @throws \RuntimeException
     */
    private function fail(string $stage, string $message): void
    {
        Log::error($message);
        $this->broadcastStage('failed', 'error', $message, $stage);

        throw new \RuntimeException($message);
    }

    /**
     * Laravel вызывает этот метод при ЛЮБОМ окончательном провале job'а — в том
     * числе минуя fail(): таймаут воркера (MaxAttemptsExceededException при
     * $tries = 1 после убийства зависшего процесса), нехватка памяти, обрыв
     * соединения с БД и т.п. Без этого хука карточка пакета на фронте навсегда
     * остаётся в статусе "running" последнего этапа, потому что failed-событие
     * для неё так и не приходит.
     */
    public function failed(\Throwable $exception): void
    {
        if ($this->failureAlreadyBroadcast) {
            return;
        }

        Log::error('ProcessCertificateUpload: незапланированный сбой — ' . $exception->getMessage());
        $this->broadcastStage('failed', 'error', 'Непредвиденная ошибка при обработке сертификата: ' . $exception->getMessage(), $this->currentStage);
    }

    /**
     * Проверяет срок действия сертификата (notBefore/notAfter) относительно
     * текущего момента. В отличие от отзыва по CRL, это локальная и всегда
     * доступная проверка — просроченный сертификат блокируется независимо
     * от того, что говорит CRL (запись об отзыве могла быть уже удалена из
     * CRL удостоверяющим центром именно потому, что срок действия истёк).
     */
    private function checkExpiry(?int $validFromTimestamp, ?int $validToTimestamp): array
    {
        if ($validFromTimestamp === null || $validToTimestamp === null) {
            return [
                'status' => 'failed',
                'type' => 'error',
                'message' => 'Не удалось определить срок действия сертификата',
            ];
        }

        $now = Carbon::now();

        if ($now->getTimestamp() > $validToTimestamp) {
            return [
                'status' => 'failed',
                'type' => 'error',
                'message' => 'Сертификат просрочен: срок действия закончился ' . Carbon::createFromTimestamp($validToTimestamp)->format('d.m.Y'),
            ];
        }

        if ($now->getTimestamp() < $validFromTimestamp) {
            return [
                'status' => 'failed',
                'type' => 'error',
                'message' => 'Срок действия сертификата ещё не начался: действует с ' . Carbon::createFromTimestamp($validFromTimestamp)->format('d.m.Y'),
            ];
        }

        return [
            'status' => 'done',
            'type' => 'success',
            'message' => 'Сертификат действителен по сроку (до ' . Carbon::createFromTimestamp($validToTimestamp)->format('d.m.Y') . ')',
        ];
    }

    /**
     * Сверяет издателя сертификата со списком аккредитованных УЦ организации
     * (config('services.certificate_parser.trusted_issuers')). Список пуст по
     * умолчанию — пока он не настроен, честно сообщаем об этом, а не подделываем
     * результат проверки.
     */
    private function checkTrustChain(?string $issuerCn): array
    {
        $trusted = config('services.certificate_parser.trusted_issuers', []);

        if (empty($trusted)) {
            return [
                'status' => 'warning',
                'type' => 'warning',
                'message' => 'Список аккредитованных УЦ не настроен — издатель «' . ($issuerCn ?: '—') . '» не сверен',
            ];
        }

        if ($issuerCn && in_array($issuerCn, $trusted, true)) {
            return [
                'status' => 'done',
                'type' => 'success',
                'message' => "Издатель «{$issuerCn}» входит в список аккредитованных УЦ",
            ];
        }

        return [
            'status' => 'failed',
            'type' => 'error',
            'message' => 'Издатель «' . ($issuerCn ?: '—') . '» не найден в списке аккредитованных УЦ',
        ];
    }

    /**
     * Интерпретирует результат CRL-проверки из Python-парсера. Отказ сервиса
     * CRL (нет сети, нет точки распространения и т.п.) — это предупреждение,
     * а не блокирующая ошибка (fail-open): подтверждённый отзыв — единственная
     * причина останавливать загрузку.
     */
    private function describeRevocation(?array $revocation): array
    {
        if (!$revocation) {
            return [
                'status' => 'warning',
                'type' => 'warning',
                'message' => 'Сведения CRL недоступны: парсер не вернул результат проверки',
            ];
        }

        if ($revocation['revoked'] ?? false) {
            return [
                'status' => 'failed',
                'type' => 'error',
                'message' => 'Сертификат отозван удостоверяющим центром (по данным CRL)',
            ];
        }

        if (!($revocation['checked'] ?? false)) {
            return [
                'status' => 'warning',
                'type' => 'warning',
                'message' => 'Не удалось проверить список отзыва: ' . ($revocation['error'] ?? 'сервис CRL недоступен'),
            ];
        }

        return [
            'status' => 'done',
            'type' => 'success',
            'message' => 'Сертификат не найден в списке отзыва CRL',
        ];
    }

    private function moveDirectoryToStorage($sourcePath, $destinationPath): void
    {
        if (Storage::disk('certification')->exists($destinationPath)) {
            Storage::disk('certification')->deleteDirectory($destinationPath);
        }
        Storage::disk('certification')->makeDirectory($destinationPath);

        $files = Storage::disk('temp')->allFiles($sourcePath);
        foreach ($files as $file) {
            $relativePath = str_replace($sourcePath . '/', '', $file);
            $destinationFile = "$destinationPath/$relativePath";
            Storage::disk('certification')->put($destinationFile, Storage::disk('temp')->get($file));
        }
    }

    private function encryptFilesInDirectory($directory): void
    {
        $files = Storage::disk('certification')->allFiles($directory);
        foreach ($files as $file) {
            Crypto::encryptFile(Storage::disk('certification')->path($file));
        }
    }

    private function buildCertificationInfo($certificateFile, array $parsedCert, string $serialNumber): array
    {
        $certificateDir = pathinfo($certificateFile, PATHINFO_DIRNAME);
        $closeKeyValidTo = null;

        // Поиск всех header.key файлов
        $closeKeyFiles = glob($certificateDir . '/*/header.key');
        foreach ($closeKeyFiles as $closeKeyFile) {
            try {
                $closeKeyContent = file_get_contents($closeKeyFile);
                $pattern = '/\d{14}Z/';
                $patternNew = '/\d{12}Z0/';
                if (preg_match($patternNew, $closeKeyContent, $matches)) {
                    $dateString = $matches[0];
                    $closeKeyValidTo = Carbon::createFromFormat('ymdHis\Z', rtrim($dateString, 0), 'UTC')->getTimestampMs();
                    break;
                }
                if (preg_match($pattern, $closeKeyContent, $matches)) {
                    $dateString = $matches[0];
                    $closeKeyValidTo = Carbon::createFromFormat('YmdHis\Z', $dateString, 'UTC')->getTimestampMs();
                    break;
                }
            } catch (\Exception $e) {
                Log::error("Ошибка при чтении файла: $closeKeyFile", ['error' => $e->getMessage()]);
            }
        }

        $parsedSubject = $parsedCert['subject'];
        $full_name = $parsedSubject['CN'];
        $explodeFullName = Str::of($full_name)->explode(' ');
        $first_name = $explodeFullName[1];
        $middle_name = $explodeFullName[2];
        $last_name = $explodeFullName[0];

        $job_title = Str::ucfirst(Str::lower($parsedSubject['title'] ?? ''));

        return [
            'certificate' => [
                'serial_number' => $serialNumber,
                'valid_from' => Carbon::parse($parsedCert['validFrom_time_t'])->getTimestampMs(),
                'valid_to' => Carbon::parse($parsedCert['validTo_time_t'])->getTimestampMs(),
                'close_key_valid_to' => $closeKeyValidTo,
            ],
            'job_title' => $job_title,
            'full_name' => $full_name,
            'first_name' => $first_name,
            'middle_name' => $middle_name,
            'last_name' => $last_name,
            'snils' => $parsedSubject['SNILS'],
            'inn' => $parsedSubject['INN'],
        ];
    }
}
