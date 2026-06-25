<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class CreateStaffRequest extends FormRequest
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
            // Файлы папок сертификата (.cer + контейнер закрытого ключа КриптоПро),
            // загружаемые как есть, без ручной упаковки в архив.
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file'],
            // Относительные пути каждого файла внутри выбранных папок —
            // выровнены с `files` по индексу. Нужны для восстановления структуры
            // и автоматического разбиения на пакеты по числу .cer.
            'paths' => ['nullable', 'array'],
            'paths.*' => ['nullable', 'string'],
        ];
    }
}
