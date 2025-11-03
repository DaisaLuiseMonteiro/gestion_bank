<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Client;
use App\Models\Admin;
use App\Models\Compte;
use App\Models\Transaction;

class BankSeeder extends Seeder
{
    public function run(): void
    {
        // Créer 10 clients actifs avec des emails @gmail.com
        $clients = Client::factory(10)->create();

        // Créer 2 admins
        Admin::factory(2)->create();

        // Créer 2 comptes par client (1 compte chèque et 1 compte épargne)
        $comptes = collect();
        foreach ($clients as $client) {
            // Compte chèque
            $comptes->push(Compte::factory()->create([
                'client_id' => $client->id,
                'titulaire' => $client->prenom . ' ' . $client->nom,
                'type' => 'cheque',
                'statut' => 'actif'
            ]));

            // Compte épargne (seulement pour 50% des clients)
            if (rand(0, 1) === 1) {
                $comptes->push(Compte::factory()->create([
                    'client_id' => $client->id,
                    'titulaire' => $client->prenom . ' ' . $client->nom,
                    'type' => 'epargne',
                    'statut' => 'actif'
                ]));
            }
        }

        // Créer des transactions pour les comptes
        foreach ($comptes as $compte) {
            // Dépôt initial
            Transaction::create([
                'compte_id' => $compte->id,
                'type' => 'depot',
                'montant' => 100000,
                'devise' => 'FCFA',
                'description' => 'Dépôt initial',
                'dateTransaction' => now(),
                'statut' => 'valide',
            ]);

            // Quelques transactions aléatoires
            for ($i = 0; $i < rand(5, 15); $i++) {
                $type = $this->faker->randomElement(['depot', 'retrait']);
                $montant = $type === 'depot' 
                    ? $this->faker->numberBetween(10000, 50000)
                    : $this->faker->numberBetween(5000, 30000);

                Transaction::create([
                    'compte_id' => $compte->id,
                    'type' => $type,
                    'montant' => $montant,
                    'devise' => 'FCFA',
                    'description' => $type === 'depot' ? 'Dépôt' : 'Retrait',
                    'dateTransaction' => $this->faker->dateTimeBetween('-1 year', 'now'),
                    'statut' => 'valide',
                ]);
            }
        }
    }
}