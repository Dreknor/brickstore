<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IdentifyItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization wird über Middleware geprüft
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'image' => [
                'required',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:10240', // 10MB
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'image.required' => 'Bitte wählen Sie ein Bild aus.',
            'image.image' => 'Die Datei muss ein Bild sein.',
            'image.mimes' => 'Das Bild muss vom Typ JPEG, PNG oder WebP sein.',
            'image.max' => 'Das Bild darf maximal 10 MB groß sein.',
        ];
    }
}

