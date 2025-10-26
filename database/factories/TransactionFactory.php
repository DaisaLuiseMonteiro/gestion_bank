<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Transaction;
use App\Models\Compte;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        $types = ['depot', 'retrait'];
        $statuts = ['valide', 'en_attente', 'annule'];
        $devises = ['FCFA', 'USD', 'EUR']; // Changé XOF en FCFA

        $compte = Compte::inRandomOrder()->first() ?? Compte::factory()->create();

        return [
            'compte_id' => $compte->id,
            'type' => $this->faker->randomElement($types),
            'montant' => $this->faker->numberBetween(100, 100000),
            'devise' => $this->faker->randomElement($devises),
            'description' => $this->faker->sentence(),
            'dateTransaction' => $this->faker->dateTime(),
            'statut' => $this->faker->randomElement($statuts),
            'metadata' => [
                'derniereModification' => $this->faker->dateTime(),
                'version' => 1
            ],
        ];
    }
}

