<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Supprimer l'ancienne contrainte CHECK (issue de l'"enum" Laravel) pour permettre la mise à jour des valeurs
        DB::statement("ALTER TABLE clients DROP CONSTRAINT IF EXISTS clients_sexe_check");

        // Passer la colonne sexe en string(10)
        Schema::table('clients', function (Blueprint $table) {
            $table->string('sexe', 10)->change();
        });

        // Conversion des anciennes valeurs si existantes
        DB::statement("UPDATE clients SET sexe = 'masculin' WHERE sexe = 'M'");
        DB::statement("UPDATE clients SET sexe = 'feminin' WHERE sexe = 'F'");

        // Recréer une contrainte CHECK compatible avec les nouvelles valeurs
        DB::statement(<<<'SQL'
            ALTER TABLE clients
            ADD CONSTRAINT clients_sexe_check
            CHECK (sexe IN ('masculin','feminin'))
        SQL);
    }

    public function down(): void
    {
        // Supprimer la contrainte CHECK actuelle
        DB::statement("ALTER TABLE clients DROP CONSTRAINT IF EXISTS clients_sexe_check");

        // Revenir à M/F
        DB::statement("UPDATE clients SET sexe = 'M' WHERE sexe = 'masculin'");
        DB::statement("UPDATE clients SET sexe = 'F' WHERE sexe = 'feminin'");

        // Réduire la taille de la colonne
        Schema::table('clients', function (Blueprint $table) {
            $table->string('sexe', 1)->change();
        });

        // Recréer la contrainte d'origine
        DB::statement(<<<'SQL'
            ALTER TABLE clients
            ADD CONSTRAINT clients_sexe_check
            CHECK (sexe IN ('M','F'))
        SQL);
    }
};

