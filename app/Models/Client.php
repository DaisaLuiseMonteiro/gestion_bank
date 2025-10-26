<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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
 *     @OA\Property(property="email", type="string", format="email", description="Email"),
 *     @OA\Property(property="sexe", type="string", enum={"masculin","feminin"}, description="Gender"),
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
        'email',
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
            $model->validateAttributes();
        });

        static::updating(function ($model) {
            $model->validateAttributes(true);
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

    // Accès via relation si nécessaire: $client->user->email

    private function validateAttributes(bool $updating = false): void
    {
        $sexe = $this->sexe;

        $rules = [
            'nom' => ['required','string','max:255'],
            'prenom' => ['required','string','max:255'],
            'email' => ['nullable','email', Rule::unique('clients','email')->ignore($this->id)],
            'telephone' => [
                'required',
                'regex:/^(70|75|76|77|78)[0-9]{7}$/',
                Rule::unique('clients','telephone')->ignore($this->id),
            ],
            'sexe' => ['required', Rule::in(['masculin','feminin'])],
            'cni' => [
                'required',
                function($attribute,$value,$fail) use ($sexe) {
                    if ($sexe === 'masculin' && !preg_match('/^1\d{12}$/', (string)$value)) {
                        return $fail('CNI invalide pour sexe masculin (doit commencer par 1 et avoir 13 chiffres).');
                    }
                    if ($sexe === 'feminin' && !preg_match('/^2\d{12}$/', (string)$value)) {
                        return $fail('CNI invalide pour sexe feminin (doit commencer par 2 et avoir 13 chiffres).');
                    }
                },
                Rule::unique('clients','cni')->ignore($this->id),
            ],
        ];

        Validator::make($this->attributesToArray(), $rules)->validate();
    }
}