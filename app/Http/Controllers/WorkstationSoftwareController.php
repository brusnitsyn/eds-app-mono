<?php

namespace App\Http\Controllers;

use App\Models\WorkstationSoftware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WorkstationSoftwareController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'key'     => ['required', 'string', 'in:' . implode(',', array_keys(WorkstationSoftware::$keys))],
            'version' => ['nullable', 'string', 'max:32'],
            'file'    => ['required', 'file', 'max:512000'], // 500 MB
        ]);

        $file = $request->file('file');
        $path = $file->store("workstation/{$data['key']}", 'local');

        $existing = WorkstationSoftware::where('key', $data['key'])->first();
        if ($existing) {
            Storage::disk('local')->delete($existing->file_path);
            $existing->update([
                'version'       => $data['version'] ?? null,
                'file_path'     => $path,
                'original_name' => $file->getClientOriginalName(),
                'file_size'     => $file->getSize(),
            ]);
        } else {
            WorkstationSoftware::create([
                'key'           => $data['key'],
                'label'         => WorkstationSoftware::$keys[$data['key']],
                'version'       => $data['version'] ?? null,
                'file_path'     => $path,
                'original_name' => $file->getClientOriginalName(),
                'file_size'     => $file->getSize(),
            ]);
        }

        return back();
    }

    public function destroy(WorkstationSoftware $workstationSoftware)
    {
        Storage::disk('local')->delete($workstationSoftware->file_path);
        $workstationSoftware->delete();

        return back();
    }

    public function download(WorkstationSoftware $workstationSoftware)
    {
        abort_unless(Storage::disk('local')->exists($workstationSoftware->file_path), 404);

        return Storage::disk('local')->download(
            $workstationSoftware->file_path,
            $workstationSoftware->original_name
        );
    }
}
