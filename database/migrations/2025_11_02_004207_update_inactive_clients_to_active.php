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
        \DB::table('clients')
            ->where('statut', 'inactif')
            ->update(['statut' => 'actif']);
    }

    /**
     * Reverse the migrations.
     * Note: Cette opération est potentiellement destructive car on ne peut pas savoir
     * quels clients étaient inactifs avant la migration.
     */
    public function down(): void
    {
        // Impossible de restaurer l'état précédent de manière fiable
        // car on ne sait pas quels clients étaient inactifs
    }
};
