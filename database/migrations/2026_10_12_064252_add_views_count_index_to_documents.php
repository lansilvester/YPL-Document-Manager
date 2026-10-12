<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Index untuk urutan popularitas (analytics dokumen teratas) —
     * ganti filesort dengan index scan.
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->index(['views_count']);
        });

        // SQLite me-rebuild tabel saat alter sehingga trigger FTS5 hilang.
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER IF NOT EXISTS documents_ai AFTER INSERT ON documents BEGIN
                  INSERT INTO documents_fts(rowid, title, description) VALUES (new.id, new.title, new.description);
                END;
                CREATE TRIGGER IF NOT EXISTS documents_ad AFTER DELETE ON documents BEGIN
                  INSERT INTO documents_fts(documents_fts, rowid, title, description) VALUES('delete', old.id, old.title, old.description);
                END;
                CREATE TRIGGER IF NOT EXISTS documents_au AFTER UPDATE ON documents BEGIN
                  INSERT INTO documents_fts(documents_fts, rowid, title, description) VALUES('delete', old.id, old.title, old.description);
                  INSERT INTO documents_fts(rowid, title, description) VALUES (new.id, new.title, new.description);
                END;
                SQL);
            DB::statement('INSERT INTO documents_fts(documents_fts) VALUES(?)', ['rebuild']);
        }
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['views_count']);
        });
    }
};
