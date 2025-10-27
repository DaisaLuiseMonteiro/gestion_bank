<?php

namespace App\Http\Requests\Compte;

use Illuminate\Foundation\Http\FormRequest;

class ListComptesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['sometimes','integer','min:1'],
            'limit' => ['sometimes','integer','min:1','max:100'],
            'type' => ['sometimes','in:cheque,epargne'],
            'statut' => ['sometimes','in:actif,bloque,ferme'],
            'search' => ['sometimes','string','nullable'],
            'sort' => ['sometimes','in:dateCreation,titulaire'],
            'order' => ['sometimes','in:asc,desc'],
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();
        return [
            'page' => max(1, (int)($validated['page'] ?? 1)),
            'limit' => min(100, max(1, (int)($validated['limit'] ?? 10))),
            'type' => $validated['type'] ?? null,
            'statut' => $validated['statut'] ?? 'actif',
            'search' => $validated['search'] ?? null,
            'sort' => in_array(($validated['sort'] ?? ''), ['dateCreation','titulaire']) ? $validated['sort'] : 'dateCreation',
            'order' => (($validated['order'] ?? '') === 'asc') ? 'asc' : 'desc',
        ];
    }
}
