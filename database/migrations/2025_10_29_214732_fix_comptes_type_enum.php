<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Supprimer la contrainte de clé étrangère si elle existe
        Schema::table('comptes', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
        });
        
        // 2. Créer une colonne temporaire
        Schema::table('comptes', function (Blueprint $table) {
            $table->string('type_temp', 10)->default('cheque');
        });
        
        // 3. Copier les données
        \DB::statement("UPDATE comptes SET type_temp = CASE 
            WHEN type = 'courant' THEN 'cheque' 
            WHEN type IN ('cheque', 'epargne') THEN type 
            ELSE 'cheque' 
        END");
        
        // 4. Supprimer l'ancienne colonne
        Schema::table('comptes', function (Blueprint $table) {
            $table->dropColumn('type');
        });
        
        // 5. Renommer la colonne temporaire
        Schema::table('comptes', function (Blueprint $table) {
            $table->renameColumn('type_temp', 'type');
        });
        
        // 6. Recréer la contrainte de clé étrangère
        Schema::table('comptes', function (Blueprint $table) {
            $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();
        });
        
        // 7. Ajouter une contrainte de vérification
        \DB::statement("ALTER TABLE comptes ADD CONSTRAINT check_comptes_type CHECK (type IN ('cheque', 'epargne'))");
    }

    public function down(): void
    {
        // 1. Supprimer la contrainte de vérification
        \DB::statement("ALTER TABLE comptes DROP CONSTRAINT IF EXISTS check_comptes_type");
        
        // 2. Supprimer la contrainte de clé étrangère
        Schema::table('comptes', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
        });
        
        // 3. Créer une colonne temporaire
        Schema::table('comptes', function (Blueprint $table) {
            $table->string('type_old', 10)->default('cheque');
        });
        
        // 4. Copier les données
        \DB::statement("UPDATE comptes SET type_old = type");
        
        // 5. Supprimer la colonne actuelle
        Schema::table('comptes', function (Blueprint $table) {
            $table->dropColumn('type');
        });
        
        // 6. Recréer la colonne avec l'ancien type
        Schema::table('comptes', function (Blueprint $table) {
            $table->enum('type', ['courant', 'epargne', 'cheque'])->default('cheque');
        });
        
        // 7. Copier les données en arrière
        \DB::statement("UPDATE comptes SET type = type_old");
        
        // 8. Supprimer la colonne temporaire
        Schema::table('comptes', function (Blueprint $table) {
            $table->dropColumn('type_old');
        });
        
        // 9. Recréer la contrainte de clé étrangère
        Schema::table('comptes', function (Blueprint $table) {
            $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();
        });
    }
};
