<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * BAB 3/9/17: kontak orang/unit (PIC) — publik agar tamu pun dapat
     * menghubungi unit yang tepat. Opsional terkait satu unit.
     */
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 120);
            $table->string('position', 120)->nullable();
            $table->string('email', 160)->nullable();
            $table->string('phone', 40)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['unit_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
