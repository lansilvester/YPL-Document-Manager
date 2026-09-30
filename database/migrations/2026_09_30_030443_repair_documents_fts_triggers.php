<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SQLite me-rebuild tabel documents saat Schema::table menambah kolom/index/FK,
     * sehingga trigger FTS5 (documents_ai/ad/au) hilang. Buat ulang + indeks ulang.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            return;
        }

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

    public function down(): void
    {
        // Trigger dipertahankan agar pencarian tetap jalan.
    }
};
