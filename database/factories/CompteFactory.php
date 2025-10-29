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
        // 80% de chance d'être actif, 15% bloqué, 5% fermé
        $statut = $this->faker->randomElement([
            'actif' => 80,
            'bloque' => 15,
            'ferme' => 5
        ]);

        // Noms sénégalais courants pour les titulaires
        $prenomsHomme = ['Mamadou', 'Ibrahima', 'Moussa', 'Abdoulaye', 'Ousmane', 'Cheikh', 'Modou', 'Amadou', 'Samba', 'Babacar'];
        $prenomsFemme = ['Fatou', 'Aminata', 'Mariama', 'Aïssatou', 'Khadija', 'Ndeye', 'Adama', 'Seynabou', 'Astou', 'Diarra'];
        $nomsFamille = ['Diop', 'Ndiaye', 'Sarr', 'Fall', 'Ba', 'Gaye', 'Sow', 'Sy', 'Diallo', 'Thiam'];

        $sexe = $this->faker->randomElement(['M', 'F']);
        $prenom = $sexe === 'M' 
            ? $this->faker->randomElement($prenomsHomme) 
            : $this->faker->randomElement($prenomsFemme);
        $nom = $this->faker->randomElement($nomsFamille);
        $titulaire = $prenom . ' ' . $nom;

        $client = Client::inRandomOrder()->first() ?? Client::factory()->create();

        return [
            'client_id' => $client->id,
            // numeroCompte sera généré par le modèle
            'titulaire' => $titulaire,
            'type' => $this->faker->randomElement($types),
            'devise' => 'FCFA',
            'dateCreation' => $this->faker->dateTimeBetween('-5 years', 'now'),
            'statut' => $statut,
            'metadata' => [
                'derniereModification' => $this->faker->dateTimeBetween('-1 year', 'now'),
                'version' => $this->faker->numberBetween(1, 5),
                'creePar' => 'system',
                'derniereOperation' => $statut === 'actif' 
                    ? $this->faker->randomElement(['depot', 'retrait', 'virement'])
                    : 'mise_a_jour_statut'
            ],
            'created_at' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now')
        ];
    }

    /**
     * Indique que le compte doit être actif
     */
    public function actif(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'actif',
        ]);
    }

    /**
     * Indique que le compte doit être bloqué
     */
    public function bloque(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'bloque',
        ]);
    }

    /**
     * Indique que le compte doit être fermé
     */
    public function ferme(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'ferme',
        ]);
    }

    /**
     * Indique que le compte doit être de type épargne
     */
    public function epargne(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'epargne',
        ]);
    }

    /**
     * Indique que le compte doit être de type chèque
     */
    public function cheque(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'cheque',
        ]);
    }
    }
}
