<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Contrainte: CNI doit commencer par 1 si masculin, 2 si feminin, et faire 13 chiffres
        DB::statement(<<<'SQL'
            ALTER TABLE clients
            ADD CONSTRAINT clients_cni_sexe_check
            CHECK (
                (sexe = 'masculin' AND cni ~ '^1[0-9]{12}$')
                OR
                (sexe = 'feminin' AND cni ~ '^2[0-9]{12}$')
            )
        SQL);
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE clients DROP CONSTRAINT IF EXISTS clients_cni_sexe_check");
    }
};
