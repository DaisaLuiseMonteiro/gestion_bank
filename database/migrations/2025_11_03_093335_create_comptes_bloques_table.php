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
        // Création de la table dans la base de données Neon
        Schema::connection('neon')->create('comptes_bloques', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numero_compte', 20)->unique();
            $table->string('titulaire');
            $table->enum('type', ['cheque', 'epargne']);
            $table->decimal('solde', 15, 2)->default(0);
            $table->string('devise', 10);
            $table->date('date_creation');
            $table->enum('statut', ['actif', 'bloque', 'ferme'])->default('bloque');
            $table->string('motif_blocage');
            $table->dateTime('date_blocage');
            $table->dateTime('date_deblocage_prevu');
            $table->json('metadata')->nullable();
            $table->string('client_id');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('neon')->dropIfExists('comptes_bloques');
    }
};
