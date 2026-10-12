<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * BAB 8: catatan pembaca per halaman (notes) — milik user, ikut hapus dokumen.
     */
    public function up(): void
    {
        Schema::create('reading_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('page')->default(1);
            $table->string('body', 1000);
            $table->timestamps();
            $table->index(['user_id', 'document_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reading_notes');
    }
};
