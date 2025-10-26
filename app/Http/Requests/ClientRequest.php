<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClientRequest extends FormRequest
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
     */ public function rules(): array
    {
        return [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|unique:clients,email|ends_with:@gmail.com',
            'password' => 'required|string|min:6|confirmed', // pour confirmation password
            'telephone' => [
                'required',
                'unique:clients,telephone',
                'regex:/^(70|75|76|77|78)\d{7}$/'
            ],
            'cni' => [
                'required',
                'unique:clients,cni',
                'size:13',
                'regex:/^[12]\d{12}$/'
            ],
            'sexe' => 'required|in:M,F',
            'adresse' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'email.ends_with' => 'L’email doit être de type @gmail.com',
            'telephone.regex' => 'Le numéro de téléphone doit commencer par 70,75,76,77 ou 78 et comporter 9 chiffres',
            'cni.regex' => 'Le CNI doit comporter 13 chiffres et commencer par 1 pour un garçon ou 2 pour une fille',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas',
        ];
    }
}
