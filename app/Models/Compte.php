<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @OA\Schema(
 *     schema="Compte",
 *     type="object",
 *     title="Account",
 *     description="Bank account model",
 *     @OA\Property(property="id", type="string", format="uuid", description="Account UUID"),
 *     @OA\Property(property="numeroCompte", type="string", description="Account number"),
 *     @OA\Property(property="titulaire", type="string", description="Account holder name"),
 *     @OA\Property(property="type", type="string", enum={"courant","epargne"}, description="Account type"),
 *     @OA\Property(property="solde", type="number", format="float", description="Account balance"),
 *     @OA\Property(property="devise", type="string", description="Currency"),
 *     @OA\Property(property="dateCreation", type="string", format="date", description="Creation date"),
 *     @OA\Property(property="statut", type="string", enum={"actif","bloque","ferme"}, description="Account status"),
 *     @OA\Property(property="client_id", type="string", format="uuid", description="Client UUID"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Compte extends Model
{
  use HasFactory;

    // UUID comme clé primaire
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'numeroCompte',
        'titulaire',
        'type',
        // 'solde', // Supprimé car calculé dynamiquement
        'devise',
        'dateCreation',
        'statut',
        'metadata',
        'client_id',
    ];

    protected $casts = [
        'metadata' => 'array',
        'dateCreation' => 'date',
    ];

    // Génération automatique de l'UUID et numéro de compte
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }

            // Générer automatiquement le numéro de compte si non fourni
            if (empty($model->numeroCompte)) {
                $model->numeroCompte = self::generateNumeroCompte();
            }

            // Définir la date de création si non fournie
            if (empty($model->dateCreation)) {
                $model->dateCreation = now();
            }
        });
    }

    /**
     * Générer un numéro de compte unique
     */
    private static function generateNumeroCompte(): string
    {
        do {
            $numero = 'C' . str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
        } while (self::where('numeroCompte', $numero)->exists());

        return $numero;
    }

    // Relation : un compte appartient à un client
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    // Relation : un compte a plusieurs transactions
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // Méthode pour calculer le solde : somme des dépôts - somme des retraits
    public function getSolde()
    {
        $deposits = $this->transactions()->where('type', 'depot')->sum('montant');
        $withdrawals = $this->transactions()->where('type', 'retrait')->sum('montant');
        return $deposits - $withdrawals;
    }
}
