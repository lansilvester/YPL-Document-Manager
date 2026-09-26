<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // FTS5: index full-text eksternal (ringan, terpisah dari tabel utama)
            DB::statement("CREATE VIRTUAL TABLE IF NOT EXISTS documents_fts USING fts5(title, description, content='documents', content_rowid='id')");
            DB::statement('INSERT INTO documents_fts(rowid, title, description) SELECT id, title, description FROM documents');
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
        } elseif ($driver === 'mysql') {
            DB::statement('ALTER TABLE documents ADD FULLTEXT documents_ft_idx (title, description)');
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::unprepared('DROP TRIGGER IF EXISTS documents_ai; DROP TRIGGER IF EXISTS documents_ad; DROP TRIGGER IF EXISTS documents_au;');
            DB::statement('DROP TABLE IF EXISTS documents_fts');
        } elseif ($driver === 'mysql') {
            DB::statement('ALTER TABLE documents DROP INDEX documents_ft_idx');
        }
    }
};
