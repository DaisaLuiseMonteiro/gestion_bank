<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1) Ajouter les colonnes en nullable pour éviter la violation NOT NULL sur les données existantes
        Schema::table('admins', function (Blueprint $table) {
            if (!Schema::hasColumn('admins', 'login')) {
                $table->string('login')->nullable()->after('department');
            }
            if (!Schema::hasColumn('admins', 'password')) {
                $table->string('password')->nullable()->after('login');
            }
        });

        // 2) Backfill: générer un login unique et un mot de passe hashé pour les lignes existantes
        $admins = DB::table('admins')->select('id', 'login', 'password')->get();
        foreach ($admins as $a) {
            $login = $a->login;
            if (empty($login)) {
                $login = 'admin_' . substr((string) $a->id, 0, 8);
            }
            $password = $a->password;
            if (empty($password)) {
                $password = password_hash('admin123', PASSWORD_BCRYPT);
            }
            DB::table('admins')->where('id', $a->id)->update([
                'login' => $login,
                'password' => $password,
            ]);
        }

        // 3) Imposer NOT NULL et l'unicité de login (PostgreSQL)
        DB::statement("ALTER TABLE admins ALTER COLUMN login SET NOT NULL;");
        DB::statement("ALTER TABLE admins ALTER COLUMN password SET NOT NULL;");
        // Créer l'index unique si non existant
        DB::statement("DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_class c JOIN pg_namespace n ON n.oid=c.relnamespace WHERE c.relname = 'admins_login_unique' AND n.nspname = 'public') THEN CREATE UNIQUE INDEX admins_login_unique ON admins(login); END IF; END $$;");
    }

    public function down(): void
    {
        // Supprimer l'index unique si présent
        DB::statement("DO $$ BEGIN IF EXISTS (SELECT 1 FROM pg_class c JOIN pg_namespace n ON n.oid=c.relnamespace WHERE c.relname = 'admins_login_unique' AND n.nspname = 'public') THEN DROP INDEX admins_login_unique; END IF; END $$;");

        Schema::table('admins', function (Blueprint $table) {
            if (Schema::hasColumn('admins', 'password')) {
                $table->dropColumn('password');
            }
            if (Schema::hasColumn('admins', 'login')) {
                $table->dropColumn('login');
            }
        });
    }
};
