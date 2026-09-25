<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Standardisasi nama peran: user -> anggota
        DB::table('users')->where('role', 'user')->update(['role' => 'anggota']);

        Schema::create('role_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('requested_role', 20); // staff|admin (superadmin tidak bisa diminta)
            $table->string('reason', 500)->nullable();
            $table->string('status', 20)->default('pending'); // pending|approved|rejected
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_requests');
        DB::table('users')->where('role', 'anggota')->update(['role' => 'user']);
    }
};
