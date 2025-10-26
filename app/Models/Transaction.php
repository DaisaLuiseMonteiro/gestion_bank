<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


class Transaction extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'compte_id',
        'type',
        'montant',
        'devise',
        'description',
        'dateTransaction',
        'statut',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'dateTransaction' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    // Relation : une transaction appartient à un compte
    public function compte()
    {
        return $this->belongsTo(Compte::class);
    }
}