<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // autoriser tous les utilisateurs pour l'instant
    }

    public function rules(): array
    {
        return [
            'client_id' => 'required|exists:clients,id', // le compte doit appartenir à un client existant
            'numeroCompte' => 'required|string|unique:comptes,numeroCompte|max:20',
            'titulaire' => 'required|string|max:255',
            'type' => 'required|in:epargne,cheque',
            'solde' => 'required|numeric|min:0',
            'devise' => 'required|string|max:5',
            'dateCreation' => 'required|date',
            'statut' => 'required|in:actif,bloque,ferme',
            'metadata' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.exists' => 'Le client sélectionné n’existe pas.',
            'numeroCompte.unique' => 'Ce numéro de compte existe déjà.',
            'type.in' => 'Le type de compte doit être "epargne" ou "cheque".',
            'solde.min' => 'Le solde ne peut pas être négatif.',
            'statut.in' => 'Le statut doit être "actif", "bloque" ou "ferme".',
        ];
    }
}
