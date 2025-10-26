<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Passer la colonne sexe en string et convertir M/F -> masculin/feminin
        Schema::table('clients', function (Blueprint $table) {
            $table->string('sexe', 10)->change();
        });

        // Conversion des anciennes valeurs si existantes
        DB::statement("UPDATE clients SET sexe = 'masculin' WHERE sexe = 'M'");
        DB::statement("UPDATE clients SET sexe = 'feminin' WHERE sexe = 'F'");
    }

    public function down(): void
    {
        // Revenir à M/F (si vous utilisiez MySQL enum à l'origine, adaptez au besoin)
        // Ici on revient à string(1) puis on remappe
        DB::statement("UPDATE clients SET sexe = 'M' WHERE sexe = 'masculin'");
        DB::statement("UPDATE clients SET sexe = 'F' WHERE sexe = 'feminin'");

        Schema::table('clients', function (Blueprint $table) {
            $table->string('sexe', 1)->change();
        });
    }
};
