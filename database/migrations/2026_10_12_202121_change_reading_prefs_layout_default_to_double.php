<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reader desktop memakai two-page spread sebagai default (BAB 20).
     * Mobile tetap single-page lewat penyesuaian adaptif di sisi klien.
     */
    public function up(): void
    {
        Schema::table('reading_prefs', function (Blueprint $table) {
            $table->string('layout', 10)->default('double')->change();
        });
    }

    public function down(): void
    {
        Schema::table('reading_prefs', function (Blueprint $table) {
            $table->string('layout', 10)->default('single')->change();
        });
    }
};
