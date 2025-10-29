<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BlockerCompteRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'motif' => 'required|string|max:255',
            'duree' => 'required|integer|min:1',
            'unite' => 'required|in:jours,semaines,mois,annees'
        ];
    }
}
