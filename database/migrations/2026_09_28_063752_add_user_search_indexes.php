<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            // Cari pengguna yang optimal: FULLTEXT (name,email) + filter peran & urutan tanggal.
            DB::statement('ALTER TABLE users ADD INDEX users_role_index (role)');
            DB::statement('ALTER TABLE users ADD INDEX users_created_at_index (created_at)');
            DB::statement('ALTER TABLE users ADD FULLTEXT users_name_email_ft_idx (name, email)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE users DROP INDEX users_role_index');
            DB::statement('ALTER TABLE users DROP INDEX users_created_at_index');
            DB::statement('ALTER TABLE users DROP INDEX users_name_email_ft_idx');
        }
    }
};
