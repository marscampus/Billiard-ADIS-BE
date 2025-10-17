<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Adjust this based on your authorization logic
    }

    public function rules()
    {
        return [
            'invoice_number' => [
                'required',
                Rule::unique('invoices')->ignore($this->route('invoice'))
            ],
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'from' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'notes' => 'nullable|string',
            'queued' => 'nullable|boolean',
            'subtotal' => 'required|numeric|min:0',
            'tax' => 'required|numeric|min:0',
            'amount_paid' => 'required|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:invoice_items,id',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.amount' => 'required|numeric|min:0',
        ];
    }
}
