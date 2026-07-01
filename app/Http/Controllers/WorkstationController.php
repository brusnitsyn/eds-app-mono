<?php

namespace App\Http\Controllers;

use App\Models\WorkstationSoftware;
use Inertia\Inertia;

class WorkstationController extends Controller
{
    public function index()
    {
        $software = WorkstationSoftware::all()
            ->keyBy('key')
            ->map(fn ($s) => [
                'id'            => $s->id,
                'key'           => $s->key,
                'label'         => $s->label,
                'version'       => $s->version,
                'original_name' => $s->original_name,
                'file_size'     => $s->file_size,
                'download_url'  => route('workstation-software.download', $s->id),
            ]);

        return Inertia::render('Workstation/Index', compact('software'));
    }
}
