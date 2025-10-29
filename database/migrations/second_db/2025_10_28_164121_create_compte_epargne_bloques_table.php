<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::connection('epargne_bloque')->create('compte_epargne_bloques', function (Blueprint $table) {
            $table->id();
            $table->string('numero_compte')->unique();
            $table->foreignId('client_id')->constrained('clients');
            $table->decimal('solde', 15, 2);
            $table->enum('type_compte', ['epargne', 'courant', 'bloque']);
            $table->string('devise', 3)->default('EUR');
            $table->string('statut')->default('bloque');
            $table->dateTime('date_blocage');
            $table->dateTime('date_deblocage');
            $table->text('motif_blocage');
            $table->string('bloque_par');
            $table->string('debloque_par')->nullable();
            $table->text('motif_deblocage')->nullable();
            $table->boolean('deblocage_automatique')->default(false);
            $table->timestamps();
            
            $table->index('date_deblocage');
            $table->index('statut');
        });
    }

    public function down()
    {
        Schema::connection('epargne_bloque')->dropIfExists('compte_epargne_bloques');
    }
};
