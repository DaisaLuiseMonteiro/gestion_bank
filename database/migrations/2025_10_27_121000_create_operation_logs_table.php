<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operation_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('admin_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('operation', 32); // create|update|delete
            $table->string('resource', 64)->default('comptes');
            $table->string('method', 16);
            $table->string('path', 255);
            $table->string('ip', 64)->nullable();
            $table->text('message')->nullable();
            $table->json('payload')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->timestamps();

            $table->index(['resource','operation']);
            $table->index('admin_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operation_logs');
    }
};
