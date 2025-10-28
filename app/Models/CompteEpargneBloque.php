<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompteEpargneBloque extends Model
{
    /**
     * La connexion utilisée par le modèle.
     *
     * @var string
     */
    protected $connection = 'epargne_bloque';

    /**
     * Le nom de la table associée au modèle.
     *
     * @var string
     */
    protected $table = 'compte_epargne_bloques';

    /**
     * Les attributs qui sont mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'numero_compte',
        'client_id',
        'solde',
        'date_blocage',
        'date_deblocage',
        'taux_interet',
        'statut',
    ];

    /**
     * Les attributs qui doivent être convertis en types natifs.
     *
     * @var array
     */
    protected $casts = [
        'date_blocage' => 'datetime',
        'date_deblocage' => 'datetime',
        'solde' => 'decimal:2',
        'taux_interet' => 'decimal:2',
    ];
}
