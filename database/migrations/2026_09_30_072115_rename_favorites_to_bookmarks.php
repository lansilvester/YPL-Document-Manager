<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('favorites', 'bookmarks');
        DB::table('settings')->where('key', 'favorite_enabled')->update(['key' => 'bookmark_enabled']);
        DB::table('activity_logs')->where('action', 'favorite')->update(['action' => 'bookmark']);
    }

    public function down(): void
    {
        Schema::rename('bookmarks', 'favorites');
        DB::table('settings')->where('key', 'bookmark_enabled')->update(['key' => 'favorite_enabled']);
        DB::table('activity_logs')->where('action', 'bookmark')->update(['action' => 'favorite']);
    }
};
