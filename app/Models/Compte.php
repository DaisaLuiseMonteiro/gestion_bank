<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Modèle représentant un compte bancaire
 * 
 * Le numéro de compte est généré automatiquement au format 'C' suivi de 8 chiffres (ex: C00123456)
 * et ne peut pas être modifié manuellement.
 * 
 * @OA\Schema(
 *     schema="Compte",
 *     type="object",
 *     title="Account",
 *     description="Modèle de compte bancaire avec génération automatique du numéro de compte",
 *     @OA\Property(property="id", type="string", format="uuid", description="UUID unique du compte"),
 *     @OA\Property(
 *         property="numeroCompte", 
 *         type="string", 
 *         readOnly=true,
 *         description="Numéro de compte généré automatiquement au format 'C' + 8 chiffres (ex: C00123456)"
 *     ),
 *     @OA\Property(property="titulaire", type="string", description="Nom du titulaire du compte"),
 *     @OA\Property(
 *         property="type", 
 *         type="string", 
 *         enum={"cheque","epargne"}, 
 *         description="Type de compte (chèque ou épargne)"
 *     ),
 *     @OA\Property(
 *         property="solde", 
 *         type="number", 
 *         format="float", 
 *         readOnly=true,
 *         description="Solde calculé automatiquement comme la différence entre les dépôts et les retraits"
 *     ),
 *     @OA\Property(property="devise", type="string", description="Devise du compte (ex: FCFA, EUR, USD)"),
 *     @OA\Property(
 *         property="dateCreation", 
 *         type="string", 
 *         format="date", 
 *         description="Date de création du compte, définie automatiquement à la création si non fournie"
 *     ),
 *     @OA\Property(
 *         property="statut", 
 *         type="string", 
 *         enum={"actif","bloque","ferme"}, 
 *         description="Statut du compte: actif (par défaut), bloqué ou fermé"
 *     ),
 *     @OA\Property(
 *         property="client_id", 
 *         type="string", 
 *         format="uuid", 
 *         description="UUID du client propriétaire du compte"
 *     ),
 *     @OA\Property(
 *         property="dateFermeture",
 *         type="string",
 *         format="date-time",
 *         description="Date de fermeture du compte, définie automatiquement lors de la suppression logique"
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Date de création"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Dernière mise à jour"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", description="Date de suppression logique")
 * )
 * 
 * @property string $id UUID unique du compte
 * @property string $numeroCompte Numéro de compte généré automatiquement
 * @property string $titulaire Nom du titulaire du compte
 * @property string $type Type de compte (cheque ou epargne)
 * @property string $devise Devise du compte
 * @property \Illuminate\Support\Carbon $dateCreation Date de création du compte
 * @property string $statut Statut du compte (actif, bloque, ferme)
 * @property string $client_id UUID du client propriétaire
 * @property ?\Illuminate\Support\Carbon $dateFermeture Date de fermeture du compte
 * @property array|null $metadata Métadonnées supplémentaires du compte
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property ?\Illuminate\Support\Carbon $deleted_at
 */
class Compte extends Model
{
    use HasFactory, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'titulaire',
        'type',
        'devise',
        'dateCreation',
        'statut',
        'metadata',
        'client_id',
    ];


    protected $casts = [
        'metadata' => 'array',
        'dateCreation' => 'date',
        'dateFermeture' => 'datetime',
    ];

    protected $dates = ['deleted_at'];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            // Générer un ID unique si nécessaire
            $keyName = $model->getKeyName();
            if (empty($model->$keyName)) {
                $model->$keyName = (string) Str::uuid();
            }

            // Définir la date de création si elle n'est pas définie
            if (empty($model->dateCreation)) {
                $model->dateCreation = now();
            }
            
            // Générer un numéro de compte unique
            do {
                $numero = 'C' . str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
            } while (self::where('numeroCompte', $numero)->withTrashed()->exists());
            
            $model->numeroCompte = $numero;
        });
    }

    // Suppression de la méthode setNumeroCompteAttribute si elle existe

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function getSolde()
    {
        $deposits = $this->transactions()->where('type', 'depot')->sum('montant');
        $withdrawals = $this->transactions()->where('type', 'retrait')->sum('montant');
        return $deposits - $withdrawals;
    }
}
