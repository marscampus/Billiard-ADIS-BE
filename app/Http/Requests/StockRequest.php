<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ID' => 'nullable|max:20',
            'KODE' => 'required|string|max:20',
            'KODE_TOKO' => 'nullable|string|max:20',
            'NAMA' => 'required|string|max:255',
            'HB' => 'required|numeric',
            'HJ' => 'required|numeric',
        ];
    }
}
