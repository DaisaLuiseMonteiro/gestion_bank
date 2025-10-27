<?php

namespace App\Http\Requests\Compte;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteCompteRequest extends FormRequest
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
            'motif_fermeture' => 'required|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'motif_fermeture.required' => 'Le motif de fermeture est requis',
            'motif_fermeture.string' => 'Le motif de fermeture doit être une chaîne de caractères',
            'motif_fermeture.max' => 'Le motif de fermeture ne doit pas dépasser 255 caractères',
        ];
    }
}
