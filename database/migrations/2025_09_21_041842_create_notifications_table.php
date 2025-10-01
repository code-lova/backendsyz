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
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_uuid');
            $table->string('type'); // booking_assigned, support_reply, new_registration, etc.
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable(); // Additional data like IDs, references
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index('user_uuid');
            $table->index('type');
            $table->index('read_at');
            $table->index(['user_uuid', 'read_at']);
            $table->index('created_at');

            // Foreign key
            $table->foreign('user_uuid')->references('uuid')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
