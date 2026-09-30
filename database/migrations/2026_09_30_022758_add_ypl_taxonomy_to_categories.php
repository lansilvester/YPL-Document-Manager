<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('code', 10)->nullable()->after('slug');
            $table->string('collection', 30)->nullable()->after('code');
            $table->string('color', 20)->nullable()->after('collection');
            $table->unsignedInteger('sort_order')->default(99)->after('color');
            $table->index(['collection', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['collection', 'sort_order']);
            $table->dropColumn(['code', 'collection', 'color', 'sort_order']);
        });
    }
};
