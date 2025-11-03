<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Supprimer la contrainte si elle existe déjà
        DB::statement("ALTER TABLE comptes DROP CONSTRAINT IF EXISTS comptes_type_check");
        
        // Mettre à jour les comptes de type 'courant' vers 'cheque'
        DB::table('comptes')
            ->where('type', 'courant')
            ->update(['type' => 'cheque']);

        // Modifier la colonne pour n'accepter que 'cheque' et 'epargne'
        DB::statement("ALTER TABLE comptes 
            ALTER COLUMN type 
            TYPE VARCHAR(255) 
            USING (CASE WHEN type IN ('cheque', 'epargne') THEN type ELSE 'cheque' END)::VARCHAR(255)");
            
        // Ajouter la contrainte si elle n'existe pas déjà
        $constraintExists = DB::selectOne(
            "SELECT 1 FROM pg_constraint WHERE conname = 'comptes_type_check'"
        );
        
        if (!$constraintExists) {
            DB::statement("ALTER TABLE comptes 
                ADD CONSTRAINT comptes_type_check 
                CHECK (type IN ('cheque', 'epargne'))");
        }
    }

    public function down()
    {
        // Laisser la contrainte telle quelle, le rollback de la migration annulera les changements
    }
};
