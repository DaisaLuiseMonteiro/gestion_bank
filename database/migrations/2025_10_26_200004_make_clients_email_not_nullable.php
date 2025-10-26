<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Normaliser et remplir les emails manquants avec une valeur unique
        // - Convertit en minuscule
        // - Remplace les espaces par des points dans nom/prenom
        // - Génère un email unique avec l'id du client
        DB::statement(<<<'SQL'
            UPDATE clients
            SET email = LOWER(
                REGEXP_REPLACE(nom, '\\s+', '.', 'g') || '.' ||
                REGEXP_REPLACE(prenom, '\\s+', '.', 'g') || '.' ||
                id || '@gmail.com'
            )
            WHERE email IS NULL OR email = ''
        SQL);

        // Forcer email en minuscule pour éviter la casse différente
        DB::statement("UPDATE clients SET email = LOWER(email)");

        // Rendre la colonne NOT NULL (PostgreSQL)
        DB::statement("ALTER TABLE clients ALTER COLUMN email SET NOT NULL");
    }

    public function down(): void
    {
        // Retirer la contrainte NOT NULL
        DB::statement("ALTER TABLE clients ALTER COLUMN email DROP NOT NULL");
    }
};

