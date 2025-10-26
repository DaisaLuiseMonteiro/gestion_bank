<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            if (!Schema::hasColumn('admins', 'login')) {
                $table->string('login')->unique()->after('department');
            }
            if (!Schema::hasColumn('admins', 'password')) {
                $table->string('password')->after('login');
            }
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            if (Schema::hasColumn('admins', 'password')) {
                $table->dropColumn('password');
            }
            if (Schema::hasColumn('admins', 'login')) {
                $table->dropUnique(['login']);
                $table->dropColumn('login');
            }
        });
    }
};
