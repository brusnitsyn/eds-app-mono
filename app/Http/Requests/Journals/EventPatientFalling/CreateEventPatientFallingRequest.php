<?php

namespace App\Http\Requests\Journals\EventPatientFalling;

use Illuminate\Foundation\Http\FormRequest;

class CreateEventPatientFallingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'event_at' => ['required', 'numeric'],
            'division_id' => ['required', 'numeric'],
            'full_name_patient' => ['required', 'string'],
            'reason_event' => ['required', 'string'],
            'place_event' => ['required', 'string'],
            'has_helping' => ['required', 'string'],
            'consequences' => ['required', 'string'],
        ];
    }
}
