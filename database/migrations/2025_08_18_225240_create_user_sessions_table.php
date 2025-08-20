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
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('user_uuid');
            $table->string('ip_address')->nullable();
            $table->string('device')->nullable();
            $table->string('platform')->nullable(); // Windows, MacOS, iOS, Android
            $table->string('browser')->nullable(); // Chrome, Safari, Edge
            $table->string('status')->default('active'); // active, expired
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->foreign('user_uuid')
                ->references('uuid')
                ->on('users')
                ->onDelete('cascade');

            $table->index('user_uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
};
