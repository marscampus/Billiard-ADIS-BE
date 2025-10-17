<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MasterJasaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'KODE' => 'required|string|max:50',
            'KETERANGAN' => 'nullable|string|max:255',
            'ESTIMASIHARGA' => 'nullable|numeric',
        ];
    }
}
