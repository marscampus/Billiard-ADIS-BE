<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NotaServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'STATUS' => 'required|integer',
            'KODE' => 'required|string|max:20',
            'TGL' => 'required|date',
            'ESTIMASISELESAI' => 'required|date',
            'PEMILIK' => 'required|string|max:50',
            'NOTELEPON' => 'required|string|max:50',
            'ESTIMASIHARGA' => 'required|numeric',
            'DP' => 'required|numeric',
            'PENERIMA' => 'required|string|max:50',
            'barangList' => 'required|array',
            'barangList.*.KODE' => 'required|string',
            'barangList.*.NAMA' => 'required|string',
            'barangList.*.KETERANGAN' => 'required|string',
            'barangList.*.STATUSAMBIL' => 'required|string',
            'barangList.*.ESTIMASIHARGA' => 'required|numeric',
            'barangList.*.services' => 'required|array',
            'barangList.*.services.*.KODE' => 'required|string',
            'barangList.*.services.*.HARGA' => 'required|numeric',
            'barangList.*.services.*.TYPE' => 'required|string',
        ];
    }
}
