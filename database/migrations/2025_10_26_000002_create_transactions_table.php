<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('compte_id');
            $table->enum('type', ['depot','retrait']);
            $table->decimal('montant', 15, 2);
            $table->string('devise')->default('FCFA');
            $table->string('description')->nullable();
            $table->dateTime('dateTransaction')->nullable();
            $table->string('statut')->default('valide');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('compte_id')->references('id')->on('comptes')->cascadeOnDelete();
            $table->index(['type','dateTransaction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
