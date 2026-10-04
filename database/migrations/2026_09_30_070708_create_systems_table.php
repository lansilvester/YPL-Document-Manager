<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('systems', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 60)->unique();
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->string('url', 255)->nullable();
            $table->timestamps();
        });

        // Relasi kini bisa menunjuk dokumen ATAU sistem: bangun ulang dengan
        // kedua FK nullable (turunkan ulang seed relasi setelah migrate).
        Schema::dropIfExists('document_relations');
        Schema::create('document_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_id')->nullable()->constrained('documents')->cascadeOnDelete();
            $table->foreignId('system_id')->nullable()->constrained()->nullOnDelete();
            $table->string('relation_type', 30)->default('related');
            $table->timestamps();
            $table->unique(['document_id', 'related_id', 'system_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_relations');
        Schema::create('document_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_id')->constrained('documents')->cascadeOnDelete();
            $table->string('relation_type', 30)->default('related');
            $table->timestamps();
            $table->unique(['document_id', 'related_id']);
        });
        Schema::dropIfExists('systems');
    }
};
