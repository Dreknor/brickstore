<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderShippingRequest extends FormRequest
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
            'tracking_number' => ['required', 'string', 'max:255'],
            'tracking_link' => ['nullable', 'url', 'max:500'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'tracking_number.required' => 'Bitte geben Sie eine Sendungsnummer ein.',
            'tracking_number.max' => 'Die Sendungsnummer darf maximal 255 Zeichen lang sein.',
            'tracking_link.url' => 'Bitte geben Sie eine gültige URL ein.',
            'tracking_link.max' => 'Der Tracking-Link darf maximal 500 Zeichen lang sein.',
        ];
    }
}
