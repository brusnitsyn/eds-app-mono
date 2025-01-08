<?php

namespace App\Http\Controllers;

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
        return Inertia::render('Journal/PatientFalling', [
            'data' => JournalEventPatientFalling::all()
        ]);
    }
}
