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
        Schema::create('clash_match_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clash_match_id')->constrained()->cascadeOnDelete();
            $table->foreignId('atacante_user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('bando_atacante', ['rojo', 'azul']);
            $table->enum('tipo_golpe', ['punio', 'patada', 'especial']);
            $table->integer('danio');
            $table->integer('rojo_hp_restante');
            $table->integer('azul_hp_restante');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clash_match_logs');
    }
};
