<?php

namespace Database\Seeders;

use App\Models\Compte;
use App\Models\Client;
use Illuminate\Database\Seeder;

class CompteSeeder extends Seeder
{
    public function run(): void
    {
        // Vérifier s'il y a déjà des comptes
        if (Compte::count() > 0) {
            $this->command->info('Des comptes existent déjà. Aucun compte créé.');
            return;
        }

        // Vérifier s'il y a des clients
        if (Client::count() === 0) {
            $this->command->info('Création de 10 clients...');
            Client::factory()->count(10)->create();
        }

        $this->command->info('Création de 50 comptes (dont 40 actifs, 7 bloqués et 3 fermés)...');
        
        // Créer 40 comptes actifs
        Compte::factory()
            ->count(40)
            ->actif()
            ->create();

        // Créer 7 comptes bloqués
        Compte::factory()
            ->count(7)
            ->bloque()
            ->create();

        // Créer 3 comptes fermés
        Compte::factory()
            ->count(3)
            ->ferme()
            ->create();

        $this->command->info('50 comptes créés avec succès!');
    }
}
