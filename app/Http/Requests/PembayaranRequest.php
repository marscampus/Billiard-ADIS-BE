<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PembayaranRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'FAKTUR' => 'required|string',
            'KODE' => 'required|string',
            'TGLBAYAR' => 'required|string',
            'ESTIMASISELESAI' => 'required|string',
            'DP' => 'required|numeric',
            'NOMINALBAYAR' => 'required|numeric',
            'ESTIMASIHARGA' => 'required|numeric',
            'HARGA' => 'required|numeric',
        ];
    }
}
