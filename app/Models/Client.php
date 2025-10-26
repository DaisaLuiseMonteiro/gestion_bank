<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @OA\Schema(
 *     schema="Client",
 *     type="object",
 *     title="Client",
 *     description="Client model",
 *     @OA\Property(property="id", type="string", format="uuid", description="Client UUID"),
 *     @OA\Property(property="user_id", type="integer", description="User ID"),
 *     @OA\Property(property="nom", type="string", description="Last name"),
 *     @OA\Property(property="prenom", type="string", description="First name"),
 *     @OA\Property(property="telephone", type="string", description="Phone number"),
 *     @OA\Property(property="cni", type="string", description="National ID"),
 *     @OA\Property(property="sexe", type="string", enum={"M","F"}, description="Gender"),
 *     @OA\Property(property="adresse", type="string", description="Address"),
 *     @OA\Property(property="statut", type="string", description="Status"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Client extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'nom',
        'prenom',
        'telephone',
        'cni',
        'sexe',
        'adresse',
        'statut',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    // Génération automatique de l'UUID avant création
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    // Relation avec User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relation avec comptes
    public function comptes()
    {
        return $this->hasMany(Compte::class);
    }

    // Accès aux attributs de l'utilisateur via relations
    public function getEmailAttribute()
    {
        return $this->user->email;
    }

    public function getPasswordAttribute()
    {
        return $this->user->password;
    }
}