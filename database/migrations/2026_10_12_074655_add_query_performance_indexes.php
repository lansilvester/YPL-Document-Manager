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
        $hasRelatedIdIndex = collect(Schema::getIndexes('document_relations'))
            ->contains(fn (array $index) => ($index['columns'][0] ?? null) === 'related_id');
        if (! $hasRelatedIdIndex) {
            Schema::table('document_relations', function (Blueprint $table) {
                $table->index('related_id', 'document_relations_related_id_idx');
            });
        }

        Schema::table('reading_positions', function (Blueprint $table) {
            $table->index(['user_id', 'updated_at', 'document_id'], 'reading_positions_user_recent_idx');
            $table->index(['document_id', 'percent', 'user_id'], 'reading_positions_document_percent_user_idx');
        });

        Schema::table('bookmarks', function (Blueprint $table) {
            $table->index(['user_id', 'created_at', 'document_id'], 'bookmarks_user_recent_idx');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index('created_at', 'activity_logs_created_at_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex('activity_logs_created_at_idx');
        });

        Schema::table('bookmarks', function (Blueprint $table) {
            $table->dropIndex('bookmarks_user_recent_idx');
        });

        Schema::table('reading_positions', function (Blueprint $table) {
            $table->dropIndex('reading_positions_document_percent_user_idx');
            $table->dropIndex('reading_positions_user_recent_idx');
        });

        $hasAddedRelatedIdIndex = collect(Schema::getIndexes('document_relations'))
            ->contains(fn (array $index) => ($index['name'] ?? null) === 'document_relations_related_id_idx');
        if ($hasAddedRelatedIdIndex) {
            Schema::table('document_relations', function (Blueprint $table) {
                $table->dropIndex('document_relations_related_id_idx');
            });
        }
    }
};
