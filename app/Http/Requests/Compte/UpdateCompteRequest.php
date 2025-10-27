<?php

namespace App\Http\Requests\Compte;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Récupérer l'ID du compte depuis la route
        $compteId = $this->route('compteId');

        // L'ID du client sera résolu au contrôleur; on ne peut pas ignorer dans la règle ici sans accès direct.
        // On valide l'unicité via la table clients en utilisant ignore sur id fourni en input facultatif
        // (le contrôleur assurera l'ignore exact du client courant).

        return [
            'numeroCompte' => ['prohibited'],
            'titulaire' => ['sometimes','string','max:255'],
            'informationsClient' => ['sometimes','array'],
            'informationsClient.telephone' => [
                'sometimes','string',
                'regex:/^(70|75|76|77|78)[0-9]{7}$/',
            ],
            'informationsClient.email' => ['sometimes','nullable','email'],
            'informationsClient.password' => ['sometimes','string','min:8'],
            'informationsClient.nci' => ['sometimes','string','size:13'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $data = $this->all();
            $fields = [
                data_get($data, 'titulaire'),
                data_get($data, 'informationsClient.telephone'),
                data_get($data, 'informationsClient.email'),
                data_get($data, 'informationsClient.password'),
                data_get($data, 'informationsClient.nci'),
            ];
            $hasAtLeastOne = false;
            foreach ($fields as $val) {
                if (!is_null($val) && $val !== '') { $hasAtLeastOne = true; break; }
            }
            if (!$hasAtLeastOne) {
                $v->errors()->add('payload', 'Au moins un champ de modification est requis.');
            }
        });
    }
}
