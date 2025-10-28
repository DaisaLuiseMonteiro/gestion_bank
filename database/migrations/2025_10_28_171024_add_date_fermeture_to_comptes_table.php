<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Vérifier si la colonne dateFermeture n'existe pas déjà
        if (!Schema::hasColumn('comptes', 'dateFermeture')) {
            Schema::table('comptes', function (Blueprint $table) {
                $table->dateTime('dateFermeture')
                      ->nullable()
                      ->after('dateCreation')
                      ->comment('Date de fermeture du compte, null si le compte est actif');
            });
        }
    }

    public function down()
    {
        // Supprimer la colonne si elle existe
        if (Schema::hasColumn('comptes', 'dateFermeture')) {
            Schema::table('comptes', function (Blueprint $table) {
                $table->dropColumn('dateFermeture');
            });
        }
    }
};
