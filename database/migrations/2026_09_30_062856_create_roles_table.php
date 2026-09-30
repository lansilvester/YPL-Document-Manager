<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 30)->unique();
            $table->string('name', 60);
            $table->string('description', 255)->nullable();
            $table->unsignedInteger('priority')->default(50);
            $table->boolean('is_system')->default(false);
            $table->json('scopes')->nullable();
            $table->json('homepage')->nullable();
            $table->timestamps();
            $table->index(['priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
