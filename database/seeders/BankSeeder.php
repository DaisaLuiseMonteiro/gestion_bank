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
        // 10 clients
        $clients = Client::factory(10)->create();

        // 2 admins
        Admin::factory(2)->create();

        // 20 comptes actifs (2 par client)
        $comptes = collect();
        foreach ($clients as $client) {
            for ($i = 0; $i < 2; $i++) {
                $comptes->push(Compte::factory()->create([
                    'client_id' => $client->id,
                    'titulaire' => $client->prenom . ' ' . $client->nom,
                    'type' => $i % 2 === 0 ? 'epargne' : 'cheque', // Un compte épargne et un compte chèque par client
                ]));
            }
        }

        // 20 transactions (1 dépôt + 1 retrait par compte)
        foreach ($comptes as $compte) {
            Transaction::create([
                'compte_id' => $compte->id,
                'type' => 'depot',
                'montant' => 100000,
                'devise' => 'FCFA',
                'description' => 'Dépôt initial',
                'dateTransaction' => now(),
                'statut' => 'valide',
            ]);

            Transaction::create([
                'compte_id' => $compte->id,
                'type' => 'retrait',
                'montant' => 25000,
                'devise' => 'FCFA',
                'description' => 'Retrait initial',
                'dateTransaction' => now(),
                'statut' => 'valide',
            ]);
        }
    }
}
