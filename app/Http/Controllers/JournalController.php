<?php

namespace App\Http\Controllers;

use App\Models\Division;
use App\Models\JournalEventPatientFalling;
use Illuminate\Http\Request;
use Inertia\Inertia;

class JournalController extends Controller
{
    public function index()
    {
        return Inertia::render('Journal/Index', []);
    }

    public function patientFalling()
    {
        return Inertia::render('Journals/PatientFalling/Show', [
            'data' => JournalEventPatientFalling::all(),
            'divisions' => Division::all()->select(['id', 'label']),
        ]);
    }
}
