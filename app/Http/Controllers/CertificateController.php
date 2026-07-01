<?php

namespace App\Http\Controllers;

use App\Actions\Eds\ReadCertificate;
use App\Facades\MisDoctor;
use App\Models\Certification;
use App\Models\Division;
use App\Models\MisRoleTemplate;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
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

    public function index(Request $request)
    {
        return Inertia::render('Certificates/Index', [
            'directory' => $this->certificatesDirectory($request),
            'stats' => $this->buildStats(),
        ]);
    }

    public function dashboard()
    {
        $stats = $this->buildStats();
        $journalEvents = $this->journalEvents();

        return Inertia::render('Certificates/Dashboard', [
            'stats' => $stats,
            'dashboard' => $this->dashboardData($stats, $journalEvents),
        ]);
    }

    public function journal()
    {
        return Inertia::render('Certificates/Journal', [
            'journalEvents' => $this->journalEvents(),
        ]);
    }

    public function staff(Request $request)
    {
        $templates = MisRoleTemplate::with(['createUser'])->get();
        $misRolesCache = Cache::get('mis_roles', collect());

        if ($misRolesCache instanceof \Illuminate\Support\Collection && $misRolesCache->isNotEmpty()) {
            $templates = $templates->map(function ($template) use ($misRolesCache) {
                $roles = collect($template->roles)->map(function ($role) use ($misRolesCache) {
                    $match = $misRolesCache->firstWhere('RoleID', (string) $role);
                    $name = $match ? (is_array($match) ? $match['Name'] : $match->Name) : '';
                    return ['RoleID' => $role, 'Name' => $name];
                });
                return [...$template->toArray(), 'roles' => $roles];
            });
        }

        return Inertia::render('Certificates/Staff', [
            'directory' => $this->staffDirectory($request),
            'mis' => $this->misStats(),
            'templates' => $templates,
            'roles' => $misRolesCache instanceof \Illuminate\Support\Collection
                ? $misRolesCache->map(function ($i) {
                    return ['RoleID' => (int)(is_array($i) ? $i['RoleID'] : $i->RoleID), 'Name' => is_array($i) ? $i['Name'] : $i->Name];
                })->values()
                : collect(),
        ]);
    }

    public function settings()
    {
        return Inertia::render('Certificates/Settings', [
            'parser' => $this->parserInfo(),
            'storage' => [
                'disk_root' => Storage::disk('certification')->path(''),
            ],
            'mis' => $this->misStats(),
            'trustedCas' => \App\Models\TrustedCertificateAuthority::query()
                ->orderBy('type')
                ->orderBy('name')
                ->get(),
            'workstationSoftware' => \App\Models\WorkstationSoftware::all()->keyBy('key'),
        ]);
    }

    /**
     * Lightweight JSON search for the global command palette (Cmd+K). Runs
     * over the full in-memory collections (same constraint as
     * staffDirectory(): full_name/serial_number are app-level encrypted, so
     * neither LIKE nor an index can do this in SQL) but only ever returns a
     * handful of rows to the client instead of shipping every record.
     */
    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        if ($q === '') {
            return response()->json(['certificates' => [], 'staff' => []]);
        }

        $qLower = mb_strtolower($q);

        $certificates = $this->latestCertifications()
            ->filter(function (Certification $c) use ($qLower) {
                $haystack = mb_strtolower("{$c->staff->full_name} {$c->staff->snils} {$c->serial_number}");
                return str_contains($haystack, $qLower);
            })
            ->take(6)
            ->map(fn (Certification $c) => $this->present($c))
            ->values();

        $staff = $this->staffData()
            ->filter(function (array $s) use ($qLower) {
                $haystack = mb_strtolower("{$s['fio']} {$s['snils']} {$s['position']}");
                return str_contains($haystack, $qLower);
            })
            ->take(6)
            ->values();

        return response()->json(['certificates' => $certificates, 'staff' => $staff]);
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
     * Server-side paginated, status-filtered and sorted certificates list for
     * the Certificates index table. Unlike Staff's full_name/serial_number,
     * the columns status()/sorting depend on (valid_to, is_valid, revoked_at)
     * are NOT app-level encrypted, so the whole thing — filter, sort, count,
     * LIMIT/OFFSET — runs as one real SQL query. Nothing but the requested
     * page is ever loaded into PHP.
     */
    private function certificatesDirectory(Request $request): array
    {
        $status = $request->query('status', 'all');
        $sortKey = $request->query('sort_key');
        $sortOrder = $request->query('sort_order', 'asc');
        $pageSize = max(1, (int) $request->query('page_size', 12));
        $page = max(1, (int) $request->query('page', 1));

        $query = $this->statusQuery($status);

        if ($sortKey === 'valid_to' && $sortOrder !== 'default') {
            $query->orderBy('valid_to', $sortOrder === 'desc' ? 'desc' : 'asc');
        } else {
            $query->orderBy('certifications.id');
        }

        $paginator = $query->with('staff')->paginate($pageSize, ['*'], 'page', $page);

        return $paginator->through(fn (Certification $c) => $this->present($c))->toArray();
    }

    /**
     * Base query for one status bucket ('all' included), reused by both
     * certificatesDirectory() (listing) and buildStats()/dashboardData()
     * (counts + "expiring soon") so the status semantics are defined once.
     */
    private function statusQuery(string $status): \Illuminate\Database\Eloquent\Builder
    {
        $query = Certification::query()->latestPerStaff()->whereHas('staff');
        $this->applyStatusFilter($query, $status);

        return $query;
    }

    private function applyStatusFilter(\Illuminate\Database\Eloquent\Builder $query, string $status): void
    {
        if ($status === 'all') {
            return;
        }

        $nowMs = Carbon::now()->getTimestampMs();
        $soonMs = Carbon::now()->addDays(30)->getTimestampMs();

        if ($status === 'expired') {
            // "Истекли / отозваны" — matches Certification::status() returning
            // either 'expired' or 'revoked'.
            $query->where(function ($q) use ($nowMs) {
                $q->whereNotNull('revoked_at')
                    ->orWhere(function ($q2) use ($nowMs) {
                        $q2->whereNull('revoked_at')
                            ->where(function ($q3) use ($nowMs) {
                                $q3->where('is_valid', false)->orWhere('valid_to', '<', $nowMs);
                            });
                    });
            });
            return;
        }

        if ($status === 'expiring') {
            $query->whereNull('revoked_at')
                ->where('is_valid', true)
                ->where('valid_to', '>=', $nowMs)
                ->where('valid_to', '<=', $soonMs);
            return;
        }

        if ($status === 'valid') {
            $query->whereNull('revoked_at')
                ->where('is_valid', true)
                ->where('valid_to', '>', $soonMs);
        }
    }

    private function misStats(): array
    {
        return [
            'synced_count' => Staff::whereNotNull('mis_sync_at')->count(),
            'staff_total' => Staff::count(),
        ];
    }

    private function dashboardData(array $stats, Collection $journalEvents): array
    {
        $total = max($stats['total'], 1);

        $distSegments = collect([
            ['key' => 'valid', 'label' => 'Действительны', 'color' => '#18a058'],
            ['key' => 'expiring', 'label' => 'Истекают', 'color' => '#f0a020'],
            ['key' => 'expired', 'label' => 'Истекли / отозваны', 'color' => '#d03050'],
        ])->map(fn ($segment) => [
            ...$segment,
            'count' => $stats[$segment['key']],
            'width' => round($stats[$segment['key']] / $total * 100) . '%',
        ])->values();

        $staffTotal = Staff::count();

        $expiringSoon = $this->statusQuery('expiring')
            ->with('staff')
            ->orderBy('valid_to')
            ->limit(6)
            ->get()
            ->map(fn (Certification $c) => $this->present($c))
            ->values();

        return [
            'staffTotal' => $staffTotal,
            'coverage' => $staffTotal > 0 ? (int) round($stats['total'] / $staffTotal * 100) : 0,
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

    /**
     * Server-paginated/searchable MIS doctor directory. MIS doctors live in a
     * physically separate (legacy, unencrypted) database, so search/sort/
     * pagination all run as real SQL there (MisDoctorService::countDoctors()/
     * getSlice()) — nothing is loaded into PHP beyond the requested page.
     *
     * Locally-managed staff with no MIS pairing are deliberately NOT merged
     * in here anymore: their full_name is app-level encrypted, which made
     * search/sort impossible in SQL and forced loading the entire manual
     * Staff table into memory on every request just to paginate it in PHP.
     * Unpaired local staff still exist and still show up wherever they're
     * looked up directly (e.g. by certificate), they just aren't listed on
     * this directory page.
     */
    private function staffDirectory(Request $request): array
    {
        $searchValue = trim((string) $request->query('search_value', '')) ?: null;
        $pageSize = max(1, (int) $request->query('page_size', 25));
        $page = max(1, (int) $request->query('page', 1));

        $divisions = Division::query()->get(['id', 'label'])->keyBy('id');

        $total = MisDoctor::countDoctors($searchValue);
        $offset = ($page - 1) * $pageSize;
        $doctors = MisDoctor::getSlice($searchValue, $offset, $pageSize);

        $snilsList = $doctors->map(fn ($d) => $this->normalizeSnils($d['snils'] ?? null))->filter()->values();

        $matchedStaff = Staff::query()
            ->bySnilsIn($snilsList)
            ->with(['certification' => fn ($q) => $q->latest('created_at')->limit(1)])
            ->get()
            ->keyBy(fn (Staff $s) => $this->normalizeSnils($s->snils));

        $rows = $doctors->map(fn ($doctor) => $this->presentMisDoctorRow($doctor, $matchedStaff, $divisions));

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $rows->values(),
            $total,
            $pageSize,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return $paginator->toArray();
    }

    private function presentMisDoctorRow(array $doctor, Collection $matchedStaff, Collection $divisions): array
    {
        $staff = $matchedStaff->get($this->normalizeSnils($doctor['snils'] ?? null));
        $certification = $staff?->certification;

        return [
            'id' => "mis-{$doctor['id']}",
            'staff_id' => $staff?->id,
            'mis_user_id' => $doctor['id'],
            'fio' => trim("{$doctor['last_name']} {$doctor['first_name']} {$doctor['middle_name']}"),
            'position' => $doctor['prvd_name'] ?? null,
            'division_id' => $staff?->division_id,
            'division' => $staff ? $divisions->get($staff->division_id)?->label : null,
            'snils' => $doctor['snils'] ?? null,
            'cert_status' => $certification?->status(),
            'has_certificate' => $certification !== null,
            'source' => 'mis',
            'cert' => $certification ? [
                'staff_id' => $staff->id,
                'fio' => trim("{$doctor['last_name']} {$doctor['first_name']} {$doctor['middle_name']}"),
                'position' => $doctor['prvd_name'] ?? null,
                'snils' => $doctor['snils'] ?? null,
                'status' => $certification->status(),
                'serial_number' => $certification->serial_number,
                'valid_from' => $certification->valid_from,
                'valid_to' => $certification->valid_to,
                'file_certification' => $certification->file_certification,
            ] : null,
        ];
    }

    private function normalizeSnils(?string $snils): ?string
    {
        if ($snils === null || $snils === '') {
            return null;
        }

        return Str::of($snils)->replace(['-', ' '], '')->toString();
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

    private function buildStats(): array
    {
        return [
            'total' => $this->statusQuery('all')->count(),
            'valid' => $this->statusQuery('valid')->count(),
            'expiring' => $this->statusQuery('expiring')->count(),
            'expired' => $this->statusQuery('expired')->count(),
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
