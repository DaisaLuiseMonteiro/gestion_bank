<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nom');
            $table->string('prenom');
            $table->string('telephone')->unique();
            $table->string('cni')->unique();
            $table->enum('sexe', ['M','F']);
            $table->string('adresse')->nullable();
            $table->string('statut')->default('actif');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['nom','prenom']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
