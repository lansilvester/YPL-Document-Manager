<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->date('needed_at')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('handler_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('handled_note', 500)->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
