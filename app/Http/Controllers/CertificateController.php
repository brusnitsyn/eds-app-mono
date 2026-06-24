<?php

namespace App\Http\Controllers;

use App\Actions\Eds\ReadCertificate;
use App\Models\Certification;
use App\Models\Division;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Symfony\Component\Process\Process;

class CertificateController extends Controller
{
    public function read(Request $request)
    {
        $readCertificate = new ReadCertificate();
        return $readCertificate->read($request->file('certificate'));
    }

    public function index()
    {
        $certifications = $this->latestCertifications();

        return Inertia::render('Certificates/Index', [
            ...$this->overlayData($certifications),
            'stats' => $this->buildStats($certifications),
        ]);
    }

    public function dashboard()
    {
        $certifications = $this->latestCertifications();
        $stats = $this->buildStats($certifications);
        $journalEvents = $this->journalEvents();

        return Inertia::render('Certificates/Dashboard', [
            ...$this->overlayData($certifications),
            'stats' => $stats,
            'dashboard' => $this->dashboardData($certifications, $journalEvents),
        ]);
    }

    public function journal()
    {
        return Inertia::render('Certificates/Journal', [
            ...$this->overlayData(),
            'journalEvents' => $this->journalEvents(),
        ]);
    }

    public function staff()
    {
        return Inertia::render('Certificates/Staff', [
            ...$this->overlayData(),
            'divisions' => Division::query()->orderBy('label')->get(['id', 'label']),
            'mis' => $this->misStats(),
        ]);
    }

    public function settings()
    {
        return Inertia::render('Certificates/Settings', [
            ...$this->overlayData(),
            'parser' => $this->parserInfo(),
            'storage' => [
                'disk_root' => Storage::disk('certification')->path(''),
            ],
            'mis' => $this->misStats(),
            'trustedCas' => \App\Models\TrustedCertificateAuthority::query()
                ->orderBy('type')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function testParser()
    {
        $pythonBinary = config('services.certificate_parser.python_binary', 'python3');

        $process = new Process([$pythonBinary, '--version']);
        $process->setTimeout(5);

        try {
            $process->run();
            $ok = $process->isSuccessful();
            $output = trim($process->getOutput() ?: $process->getErrorOutput());
        } catch (\Throwable $e) {
            $ok = false;
            $output = $e->getMessage();
        }

        return response()->json([
            'ok' => $ok,
            'message' => $ok
                ? "Интерпретатор отвечает: {$output}"
                : ($output ?: 'Не удалось запустить интерпретатор'),
        ]);
    }

    public function revoke(Certification $certification)
    {
        $certification->update([
            'revoked_at' => Carbon::now(),
            'is_valid' => false,
        ]);

        return back();
    }

    /**
     * Certificates + staff list every Certificates page needs for the shared
     * search palette, upload wizard and detail drawer overlays.
     */
    private function overlayData(?Collection $certifications = null): array
    {
        $certifications ??= $this->latestCertifications();

        return [
            'certificates' => $certifications->map(fn (Certification $c) => $this->present($c))->values(),
            'staff' => $this->staffData(),
        ];
    }

    private function misStats(): array
    {
        return [
            'synced_count' => Staff::whereNotNull('mis_sync_at')->count(),
            'staff_total' => Staff::count(),
        ];
    }

    private function dashboardData(Collection $certifications, Collection $journalEvents): array
    {
        $statusCounts = $certifications->map(fn (Certification $c) => $c->status())->countBy();
        $total = max($certifications->count(), 1);

        $distSegments = collect([
            ['key' => 'valid', 'label' => 'Действительны', 'color' => '#18a058'],
            ['key' => 'expiring', 'label' => 'Истекают', 'color' => '#f0a020'],
            ['key' => 'expired', 'label' => 'Истекли / отозваны', 'color' => '#d03050'],
        ])->map(function ($segment) use ($statusCounts, $total) {
            $count = $segment['key'] === 'expired'
                ? $statusCounts->get('expired', 0) + $statusCounts->get('revoked', 0)
                : $statusCounts->get($segment['key'], 0);

            return [...$segment, 'count' => $count, 'width' => round($count / $total * 100) . '%'];
        })->values();

        $staffTotal = Staff::count();

        $expiringSoon = $certifications
            ->filter(fn (Certification $c) => $c->status() === 'expiring')
            ->sortBy(fn (Certification $c) => $c->valid_to)
            ->take(6)
            ->map(fn (Certification $c) => $this->present($c))
            ->values();

        return [
            'staffTotal' => $staffTotal,
            'coverage' => $staffTotal > 0 ? (int) round($certifications->count() / $staffTotal * 100) : 0,
            'distSegments' => $distSegments,
            'expiringSoon' => $expiringSoon,
            'recentEvents' => $journalEvents->take(6)->values(),
            'activity' => $this->uploadActivity(),
        ];
    }

    private function uploadActivity(): Collection
    {
        $since = Carbon::now()->subDays(6)->startOfDay();
        $counts = Certification::query()
            ->where('created_at', '>=', $since)
            ->get()
            ->groupBy(fn (Certification $c) => $c->created_at->format('Y-m-d'));

        $days = collect(range(0, 6))->map(function ($i) use ($counts) {
            $date = Carbon::now()->subDays(6 - $i);
            $key = $date->format('Y-m-d');
            $n = $counts->get($key)?->count() ?? 0;

            return ['d' => $date->locale('ru')->isoFormat('dd'), 'date' => $key, 'n' => $n];
        });

        $max = max($days->max('n'), 1);

        return $days->map(fn ($d) => [...$d, 'barH' => round($d['n'] / $max * 100) . '%'])->values();
    }

    private function parserInfo(): array
    {
        $config = config('services.certificate_parser');
        $scriptPath = Str::startsWith($config['script_path'], DIRECTORY_SEPARATOR)
            ? $config['script_path']
            : base_path($config['script_path']);

        return [
            'python_binary' => $config['python_binary'],
            'script_path' => $scriptPath,
            'script_exists' => is_file($scriptPath),
            'timeout' => (int) $config['timeout'],
        ];
    }

    private function staffData(): Collection
    {
        $divisions = Division::query()->get(['id', 'label'])->keyBy('id');

        return Staff::query()
            ->with(['certification' => fn ($q) => $q->latest('created_at')->limit(1)])
            ->get()
            ->map(function (Staff $staff) use ($divisions) {
                $certification = $staff->certification;

                return [
                    'id' => $staff->id,
                    'fio' => $staff->full_name,
                    'position' => $staff->job_title,
                    'division_id' => $staff->division_id,
                    'division' => $divisions->get($staff->division_id)?->label,
                    'snils' => $staff->snils,
                    'cert_status' => $certification?->status(),
                    'has_certificate' => $certification !== null,
                    'source' => $staff->mis_user_id !== null ? 'mis' : 'manual',
                ];
            })
            ->values();
    }

    private function latestCertifications(): Collection
    {
        return Certification::query()
            ->with('staff')
            ->latestPerStaff()
            ->get()
            ->filter(fn (Certification $c) => $c->staff !== null)
            ->values();
    }

    private function buildStats(Collection $certifications): array
    {
        $statuses = $certifications->map(fn (Certification $c) => $c->status());

        return [
            'total' => $certifications->count(),
            'valid' => $statuses->filter(fn ($s) => $s === 'valid')->count(),
            'expiring' => $statuses->filter(fn ($s) => $s === 'expiring')->count(),
            'expired' => $statuses->filter(fn ($s) => in_array($s, ['expired', 'revoked']))->count(),
        ];
    }

    private function journalEvents(): Collection
    {
        $certifications = Certification::with('staff')->get()->filter(fn (Certification $c) => $c->staff !== null);

        $uploadEvents = $certifications->map(fn (Certification $c) => [
            'id' => "upload-{$c->id}",
            'type' => 'upload',
            'detail' => 'Загружен сертификат · ' . ($c->file_certification ?: $c->serial_number),
            'owner' => $c->staff->full_name,
            'staff_id' => $c->staff_id,
            'time' => $c->created_at?->toIso8601String(),
        ]);

        $revokeEvents = $certifications
            ->filter(fn (Certification $c) => $c->revoked_at !== null)
            ->map(fn (Certification $c) => [
                'id' => "revoke-{$c->id}",
                'type' => 'revoke',
                'detail' => 'Сертификат отозван · ' . $c->serial_number,
                'owner' => $c->staff->full_name,
                'staff_id' => $c->staff_id,
                'time' => $c->revoked_at?->toIso8601String(),
            ]);

        return $uploadEvents->concat($revokeEvents)
            ->sortByDesc('time')
            ->values();
    }

    private function present(Certification $certification): array
    {
        $staff = $certification->staff;

        return [
            'id' => $certification->id,
            'staff_id' => $staff->id,
            'fio' => $staff->full_name,
            'position' => $staff->job_title,
            'snils' => $staff->snils,
            'serial_number' => $certification->serial_number,
            'valid_from' => $certification->valid_from,
            'valid_to' => $certification->valid_to,
            'close_key_valid_to' => $certification->close_key_valid_to,
            'is_valid' => $certification->is_valid,
            'is_request_new' => $certification->is_request_new,
            'revoked_at' => $certification->revoked_at?->toIso8601String(),
            'status' => $certification->status(),
            'file_certification' => $certification->file_certification,
            'created_at' => $certification->created_at?->toIso8601String(),
        ];
    }
}
