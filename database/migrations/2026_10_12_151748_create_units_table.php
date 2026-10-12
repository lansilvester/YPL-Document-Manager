<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * BAB 3/17: unit organisasi YPL sebagai entitas nyata (bukan sekadar
     * kolom teks pada dokumen). Dipakai direktori komunitas + pencarian.
     */
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->string('description', 255)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
