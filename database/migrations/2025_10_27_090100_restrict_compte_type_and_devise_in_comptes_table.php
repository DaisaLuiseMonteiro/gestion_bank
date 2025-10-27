<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("DO $$ BEGIN IF EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid JOIN pg_namespace n ON n.oid = t.relnamespace WHERE t.relname = 'comptes' AND n.nspname = 'public' AND c.contype = 'c' AND c.conname = 'comptes_type_check') THEN ALTER TABLE public.comptes DROP CONSTRAINT comptes_type_check; END IF; END $$;");
        DB::statement(<<<'SQL'
            DO $$
            DECLARE r record;
            BEGIN
                FOR r IN (
                    SELECT conname
                    FROM pg_constraint c
                    JOIN pg_class t ON t.oid = c.conrelid
                    JOIN pg_namespace n ON n.oid = t.relnamespace
                    WHERE t.relname = 'comptes'
                      AND n.nspname = 'public'
                      AND c.contype = 'c'
                      AND pg_get_constraintdef(c.oid) ILIKE '%type%'
                ) LOOP
                    EXECUTE format('ALTER TABLE public.comptes DROP CONSTRAINT %I', r.conname);
                END LOOP;
            END $$;
        SQL);

        DB::statement("UPDATE comptes SET type = 'cheque' WHERE type = 'courant'");
        DB::statement("ALTER TABLE comptes ALTER COLUMN type TYPE VARCHAR(10)");
        DB::statement("ALTER TABLE comptes ADD CONSTRAINT comptes_type_check CHECK (type IN ('cheque','epargne'))");

        DB::statement("UPDATE comptes SET devise = 'FCFA' WHERE devise IS DISTINCT FROM 'FCFA'");
        DB::statement("DO $$ BEGIN IF EXISTS (SELECT 1 FROM pg_constraint c JOIN pg_class t ON t.oid = c.conrelid JOIN pg_namespace n ON n.oid = t.relnamespace WHERE t.relname = 'comptes' AND n.nspname = 'public' AND c.contype = 'c' AND c.conname = 'comptes_devise_check') THEN ALTER TABLE public.comptes DROP CONSTRAINT comptes_devise_check; END IF; END $$;");
        DB::statement("ALTER TABLE comptes ADD CONSTRAINT comptes_devise_check CHECK (devise = 'FCFA')");
        DB::statement("ALTER TABLE comptes ALTER COLUMN devise SET DEFAULT 'FCFA'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE comptes DROP CONSTRAINT IF EXISTS comptes_type_check");
        DB::statement("ALTER TABLE comptes DROP CONSTRAINT IF EXISTS comptes_devise_check");
    }
};
