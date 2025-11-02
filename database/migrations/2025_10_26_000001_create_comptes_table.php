<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('comptes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_id');
            $table->string('numeroCompte')->unique()->nullable();
            $table->string('titulaire');
            $table->enum('type', ['courant','epargne','cheque']);
            $table->string('devise')->default('FCFA');
            $table->date('dateCreation')->nullable();
            $table->enum('statut', ['actif','bloque','ferme'])->default('actif');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();
            $table->index(['type','statut']);
            $table->index('titulaire');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comptes');
    }
};
