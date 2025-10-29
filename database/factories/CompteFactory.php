<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Compte;
use App\Models\Client;

class CompteFactory extends Factory
{
    protected $model = Compte::class;

    public function definition(): array
    {
        $types = ['epargne', 'cheque'];
        $devises = ['FCFA'];

        // Noms sénégalais courants pour les titulaires
        $prenomsHomme = ['Mamadou', 'Ibrahima', 'Moussa', 'Abdoulaye', 'Ousmane', 'Cheikh', 'Modou', 'Amadou', 'Samba', 'Babacar'];
        $prenomsFemme = ['Fatou', 'Aminata', 'Mariama', 'Aïssatou', 'Khadija', 'Ndeye', 'Adama', 'Seynabou', 'Astou', 'Diarra'];
        $nomsFamille = ['Diop', 'Ndiaye', 'Sarr', 'Fall', 'Ba', 'Gaye', 'Sow', 'Sy', 'Diallo', 'Thiam'];

        $sexe = $this->faker->randomElement(['M', 'F']);
        $prenom = $sexe === 'M' ? $this->faker->randomElement($prenomsHomme) : $this->faker->randomElement($prenomsFemme);
        $nom = $this->faker->randomElement($nomsFamille);
        $titulaire = $prenom . ' ' . $nom;

        $client = Client::inRandomOrder()->first() ?? Client::factory()->create();

        return [
            'client_id' => $client->id,
            // numeroCompte sera généré par le modèle
            'titulaire' => $titulaire,
            'type' => $this->faker->randomElement($types),
            'devise' => 'FCFA',
            'dateCreation' => $this->faker->date(),
            'statut' => 'actif',
            'metadata' => [
                'derniereModification' => $this->faker->dateTime(),
                'version' => 1
            ],
        ];
    }
}
