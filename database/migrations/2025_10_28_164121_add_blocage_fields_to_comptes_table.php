<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('comptes', function (Blueprint $table) {
            $columns = [
                'statut' => fn() => $table->string('statut')->default('actif')->after('dateFermeture'),
                'date_blocage' => fn() => $table->dateTime('date_blocage')->nullable()->after('statut'),
                'date_deblocage' => fn() => $table->dateTime('date_deblocage')->nullable()->after('date_blocage'),
                'motif_blocage' => fn() => $table->text('motif_blocage')->nullable()->after('date_deblocage'),
                'bloque_par' => fn() => $table->string('bloque_par')->nullable()->after('motif_blocage'),
                'debloque_par' => fn() => $table->string('debloque_par')->nullable()->after('bloque_par'),
                'motif_deblocage' => fn() => $table->text('motif_deblocage')->nullable()->after('debloque_par')
            ];

            foreach ($columns as $column => $callback) {
                if (!Schema::hasColumn('comptes', $column)) {
                    $callback();
                }
            }
        });
    }

    public function down()
    {
        Schema::table('comptes', function (Blueprint $table) {
            $columns = [
                'statut',
                'date_blocage',
                'date_deblocage',
                'motif_blocage',
                'bloque_par',
                'debloque_par',
                'motif_deblocage'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('comptes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
