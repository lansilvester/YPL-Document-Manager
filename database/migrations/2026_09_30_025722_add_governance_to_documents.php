<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catatan: di SQLite, Schema::table me-rebuild tabel sehingga trigger
        // FTS5 documents_ai/ad/au hilang — dibuat ulang di migrasi
        // repair_documents_fts_triggers yang berjalan sesudah ini.
        Schema::table('documents', function (Blueprint $table) {
            $table->string('doc_code', 40)->nullable()->unique()->after('slug');
            $table->string('doc_type', 30)->nullable()->after('doc_code');
            $table->string('version', 20)->default('1.0')->after('doc_type');
            $table->string('status', 20)->default('draft')->after('version');
            $table->foreignId('approver_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->date('effective_at')->nullable()->after('status');
            $table->date('review_at')->nullable()->after('effective_at');
            $table->string('classification', 20)->nullable()->after('review_at');
            $table->index(['status', 'doc_type']);
        });

        // Dokumen publik yang sudah ada dianggap ACTIVE v1.0.
        DB::table('documents')->where('visibility', 'public')->update(['status' => 'active']);

        Schema::create('document_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_id')->constrained('documents')->cascadeOnDelete();
            $table->string('relation_type', 30)->default('related');
            $table->timestamps();
            $table->unique(['document_id', 'related_id']);
        });

        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('version', 20);
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->string('change_note', 500)->nullable();
            $table->timestamps();
            $table->index(['document_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_versions');
        Schema::dropIfExists('document_relations');
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['status', 'doc_type']);
            $table->dropConstrainedForeignId('approver_id');
            $table->dropColumn(['doc_code', 'doc_type', 'version', 'status', 'effective_at', 'review_at', 'classification']);
        });
    }
};
