<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeedbackRequest extends FormRequest
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
            'rating' => ['required', 'integer', 'in:0,1,2'],
            'comment' => ['required', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rating.required' => 'Bitte wählen Sie eine Bewertung aus.',
            'rating.integer' => 'Die Bewertung muss eine ganze Zahl sein.',
            'rating.in' => 'Die Bewertung muss 0 (Lob), 1 (Neutral) oder 2 (Beschwerde) sein.',
            'comment.required' => 'Bitte geben Sie einen Kommentar ein.',
            'comment.string' => 'Der Kommentar muss ein Text sein.',
            'comment.max' => 'Der Kommentar darf maximal 500 Zeichen lang sein.',
        ];
    }

    /**
     * Get the validated rating as integer.
     */
    public function getRating(): int
    {
        return (int) $this->validated()['rating'];
    }

    /**
     * Get the validated comment.
     */
    public function getComment(): string
    {
        return $this->validated()['comment'];
    }
}
