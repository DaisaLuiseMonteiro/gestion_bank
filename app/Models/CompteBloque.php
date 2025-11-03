<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modèle représentant un compte bloqué dans la base de données Neon
 * 
 * @property string $id UUID unique du compte
 * @property string $numero_compte Numéro de compte unique
 * @property string $titulaire Nom du titulaire du compte
 * @property string $type Type de compte (cheque ou epargne)
 * @property float $solde Solde du compte
 * @property string $devise Devise du compte
 * @property \Carbon\Carbon $date_creation Date de création du compte
 * @property string $statut Statut du compte (actif, bloque, ferme)
 * @property string $motif_blocage Raison du blocage du compte
 * @property \Carbon\Carbon $date_blocage Date et heure du blocage
 * @property \Carbon\Carbon $date_deblocage_prevu Date et heure prévue pour le déblocage
 * @property array|null $metadata Métadonnées supplémentaires
 * @property string $client_id ID du client propriétaire
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class CompteBloque extends Model
{
    use SoftDeletes;

    /**
     * Le nom de la connexion à utiliser pour ce modèle.
     *
     * @var string
     */
    protected $connection = 'neon';

    /**
     * Le nom de la table associée au modèle.
     *
     * @var string
     */
    protected $table = 'comptes_bloques';

    /**
     * Indique si les IDs sont auto-incrémentés.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Le type de la clé primaire.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'numero_compte',
        'titulaire',
        'type',
        'solde',
        'devise',
        'date_creation',
        'statut',
        'motif_blocage',
        'date_blocage',
        'date_deblocage_prevu',
        'metadata',
        'client_id',
    ];

    /**
     * Les attributs qui doivent être convertis en types natifs.
     *
     * @var array
     */
    protected $casts = [
        'solde' => 'decimal:2',
        'date_creation' => 'date',
        'date_blocage' => 'datetime',
        'date_deblocage_prevu' => 'datetime',
        'metadata' => 'array',
        'deleted_at' => 'datetime',
    ];

    /**
     * Les attributs qui doivent être masqués lors de la sérialisation.
     *
     * @var array
     */
    protected $hidden = [
        'deleted_at',
    ];

    /**
     * Convertir le modèle en tableau.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'numero_compte' => $this->numero_compte,
            'titulaire' => $this->titulaire,
            'type' => $this->type,
            'solde' => (float) $this->solde,
            'devise' => $this->devise,
            'date_creation' => $this->date_creation->toDateString(),
            'statut' => $this->statut,
            'motif_blocage' => $this->motif_blocage,
            'date_blocage' => $this->date_blocage->toIso8601String(),
            'date_deblocage_prevu' => $this->date_deblocage_prevu->toIso8601String(),
            'metadata' => $this->metadata,
            'client_id' => $this->client_id,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
