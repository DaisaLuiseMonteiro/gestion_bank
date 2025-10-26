<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

   public function definition(): array
{
   $sexe = $this->faker->randomElement(['M', 'F']);

   // CNI Sénégalais : garçon commence par 1, fille par 2 + 12 chiffres
   $cniPrefix = $sexe === 'M' ? '1' : '2';
   $cniRest = $this->faker->numerify('############'); // 12 chiffres
   $cni = $cniPrefix . $cniRest;

   // Numéro téléphone Sénégalais : commence par 70, 75, 76, 77, 78
   $telPrefix = $this->faker->randomElement(['70', '75', '76', '77', '78']);
   $telephone = $telPrefix . $this->faker->numerify('#######'); // 7 chiffres

   // Noms sénégalais courants
   $prenomsHomme = ['Mamadou', 'Ibrahima', 'Moussa', 'Abdoulaye', 'Ousmane', 'Cheikh', 'Modou', 'Amadou', 'Samba', 'Babacar'];
   $prenomsFemme = ['Fatou', 'Aminata', 'Mariama', 'Aïssatou', 'Khadija', 'Ndeye', 'Adama', 'Seynabou', 'Astou', 'Diarra'];
   $nomsFamille = ['Diop', 'Ndiaye', 'Sarr', 'Fall', 'Ba', 'Gaye', 'Sow', 'Sy', 'Diallo', 'Thiam'];

   $prenom = $sexe === 'M' ? $this->faker->randomElement($prenomsHomme) : $this->faker->randomElement($prenomsFemme);
   $nom = $this->faker->randomElement($nomsFamille);

   return [
       'user_id' => \App\Models\User::factory(),
       'nom' => $nom,
       'prenom' => $prenom,
       'sexe' => $sexe,
       'telephone' => $telephone,
       'cni' => $cni,
       'adresse' => $this->faker->address(),
       'statut' => $this->faker->randomElement(['actif', 'inactif']),
       'metadata' => [
           'derniereModification' => $this->faker->dateTime(),
           'version' => 1
       ],
   ];
}
}