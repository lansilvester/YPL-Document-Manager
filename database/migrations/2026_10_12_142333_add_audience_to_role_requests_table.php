<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('role_requests', function (Blueprint $table) {
            // Peran sistem kini opsional: pengajuan bisa berupa audiens saja.
            $table->string('requested_role', 20)->nullable()->change();
            $table->string('requested_audience', 20)->nullable()->after('requested_role');
        });
    }

    public function down(): void
    {
        Schema::table('role_requests', function (Blueprint $table) {
            $table->dropColumn('requested_audience');
            $table->string('requested_role', 20)->nullable(false)->change();
        });
    }
};
