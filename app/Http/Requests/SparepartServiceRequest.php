<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SparepartServiceRequest extends FormRequest
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
            'KODE_SERVICE' => 'nullable|string|max:50',
            'KODE_BARANG' => 'nullable|string|max:50',
            'KODE' => 'nullable|string|max:50',
            'HARGA' => 'nullable|numeric',
            'STATUS' => 'nullable|string|max:1',
        ];
    }
}
