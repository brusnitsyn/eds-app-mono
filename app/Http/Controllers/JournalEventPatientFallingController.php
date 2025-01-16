<?php

namespace App\Http\Controllers;

use App\Http\Requests\Journals\EventPatientFalling\CreateEventPatientFallingRequest;
use App\Models\JournalEventPatientFalling;
use Illuminate\Http\Request;
use Inertia\Inertia;

class JournalEventPatientFallingController extends Controller
{
    public function index()
    {

    }

    public function store(CreateEventPatientFallingRequest $request)
    {
        $data = $request->validated();
        $journalEventPatientFalling = JournalEventPatientFalling::create($data);

        if (!$journalEventPatientFalling) {
        }

        return Inertia::render('Journals/PatientFalling/Show', [
            'data' => JournalEventPatientFalling::all(),
        ]);
    }

    public function show(JournalEventPatientFalling $journalEventPatientFalling)
    {

    }
}
