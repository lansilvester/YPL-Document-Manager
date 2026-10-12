<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * BAB 7: Event (Calendar) dan FAQ menjadi first-class knowledge —
     * tabel entitas + target relasi dokumen. Seed-managed seperti systems.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('category', 20)->default('event'); // foundation|academic|boarding|event|deadline
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->string('location', 120)->nullable();
            $table->string('visibility', 20)->default('public'); // public|internal
            $table->timestamps();
            $table->index(['category', 'starts_at']);
            $table->index(['visibility']);
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('question', 255);
            $table->text('answer');
            $table->string('category', 60)->nullable();
            $table->string('visibility', 20)->default('public'); // public|internal
            $table->unsignedInteger('sort_order')->default(99);
            $table->timestamps();
            $table->index(['visibility', 'sort_order']);
        });

        Schema::table('document_relations', function (Blueprint $table) {
            $table->foreignId('event_id')->nullable()->after('system_id')->constrained()->nullOnDelete();
            $table->foreignId('faq_id')->nullable()->after('event_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('document_relations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('faq_id');
            $table->dropConstrainedForeignId('event_id');
        });
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('events');
    }
};
