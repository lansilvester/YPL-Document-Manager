<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime')->nullable();
            $table->string('visibility', 20)->default('public'); // public|private|restricted
            $table->string('source_type', 20)->default('upload'); // upload|editor
            $table->longText('content')->nullable(); // HTML dari editor
            $table->unsignedBigInteger('views_count')->default(0);
            $table->unsignedBigInteger('downloads_count')->default(0);
            $table->timestamps();
            $table->index(['visibility', 'created_at']);
        });

        Schema::create('reading_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('page')->default(1);
            $table->unsignedInteger('total_pages')->nullable();
            $table->float('percent')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'document_id']);
        });

        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'document_id']);
        });

        Schema::create('reading_prefs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('theme', 10)->default('light'); // light|dark
            $table->unsignedInteger('zoom')->default(100);
            $table->string('layout', 10)->default('single'); // single|double
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 50); // view|upload|update|delete|download|favorite|register|login
            $table->json('meta')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
            $table->index(['action', 'created_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_path')->nullable()->after('password');
            $table->string('academic_info')->nullable()->after('avatar_path');
            $table->string('role', 20)->default('user')->after('academic_info');
            $table->boolean('ypl_verified')->default(false)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar_path', 'academic_info', 'role', 'ypl_verified']);
        });
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('reading_prefs');
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('reading_positions');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('categories');
    }
};
