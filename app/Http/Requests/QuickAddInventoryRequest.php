<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuickAddInventoryRequest extends FormRequest
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
            'inventory_id' => [
                'required',
                'exists:inventories,id',
            ],
            'quantity' => [
                'required',
                'integer',
                'min:1',
                'max:99999',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'inventory_id.required' => 'Inventar-ID ist erforderlich.',
            'inventory_id.exists' => 'Inventar-Eintrag wurde nicht gefunden.',
            'quantity.required' => 'Menge ist erforderlich.',
            'quantity.integer' => 'Menge muss eine Ganzzahl sein.',
            'quantity.min' => 'Menge muss mindestens 1 sein.',
            'quantity.max' => 'Menge darf maximal 99999 sein.',
        ];
    }
}

