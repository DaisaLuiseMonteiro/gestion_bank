<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Supprimer explicitement la contrainte standard si elle existe
        DB::statement("ALTER TABLE clients DROP CONSTRAINT IF EXISTS clients_sexe_check");

        // Supprimer toute contrainte CHECK existante liée à la colonne `sexe` (nom variable selon environnements)
        DB::statement(<<<'SQL'
            DO $$
            DECLARE r record;
            BEGIN
                FOR r IN (
                    SELECT conname
                    FROM pg_constraint c
                    JOIN pg_class t ON t.oid = c.conrelid
                    JOIN pg_namespace n ON n.oid = t.relnamespace
                    WHERE t.relname = 'clients'
                      AND n.nspname = 'public'
                      AND c.contype = 'c'
                      AND pg_get_constraintdef(c.oid) ILIKE '%sexe%'
                ) LOOP
                    EXECUTE format('ALTER TABLE public.clients DROP CONSTRAINT %I', r.conname);
                END LOOP;
            END $$;
        SQL);

        // Passer la colonne sexe en VARCHAR(10) sans dépendre de doctrine/dbal
        DB::statement("ALTER TABLE clients ALTER COLUMN sexe TYPE VARCHAR(10)");

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

