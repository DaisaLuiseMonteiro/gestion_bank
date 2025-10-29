<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('comptes', function (Blueprint $table) {
            $table->softDeletes();
            $table->dateTime('dateFermeture')->nullable()->after('dateCreation');
        });
    }

    public function down()
    {
        Schema::table('comptes', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn('dateFermeture');
        });
    }
};
