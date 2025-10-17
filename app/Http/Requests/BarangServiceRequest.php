<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BarangServiceRequest extends FormRequest
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
            'KODE_SERVICE' => 'required|string|max:20',
            'KODE' => 'nullable|string|max:20',
            'NAMA' => 'nullable|string|max:50',
            'KETERANGAN' => 'nullable|string|max:255',
            'QTY' => 'nullable|numeric',
            'STATUSAMBIL' => 'nullable|string|max:50',
        ];
    }
}
