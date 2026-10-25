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
        Schema::create('clash_matches', function (Blueprint $table) {
            $table->id();
            $table->string('sala')->index();
            $table->foreignId('rojo_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('azul_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('rojo_hp')->default(1000);
            $table->integer('azul_hp')->default(1000);
            $table->enum('estado', ['esperando', 'en_curso', 'finalizado'])->default('esperando');
            $table->enum('ganador', ['rojo', 'azul'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clash_matches');
    }
};
