<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class UpdateClientRequest extends FormRequest
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
        $clientId = $this->route('clientId');
        
        return [
            'nom' => 'sometimes|string|max:255',
            'prenom' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('clients', 'email')->ignore($clientId)
            ],
            'telephone' => [
                'sometimes',
                'string',
                'regex:/^(\+221|00221)?[76|77|78|70|75|76|33][0-9]{7}$/',
                Rule::unique('clients', 'telephone')->ignore($clientId)
            ],
            'password' => 'sometimes|string|min:8|nullable',
            'cni' => 'sometimes|string|max:50|unique:clients,cni,' . $clientId,
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
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'telephone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'telephone.regex' => 'Le format du numéro de téléphone est invalide. Utilisez un numéro de téléphone sénégalais valide.',
            'cni.unique' => 'Ce numéro de CNI est déjà utilisé.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Nettoyer les données avant la validation
        if ($this->has('telephone')) {
            $this->merge([
                'telephone' => preg_replace('/[^0-9+]/', '', $this->telephone),
            ]);
        }

        // Si un mot de passe est fourni, le hasher
        if ($this->has('password') && $this->password) {
            $this->merge([
                'password' => Hash::make($this->password),
            ]);
        } else {
            // Supprimer le mot de passe s'il est vide
            $this->request->remove('password');
        }
    }
}
