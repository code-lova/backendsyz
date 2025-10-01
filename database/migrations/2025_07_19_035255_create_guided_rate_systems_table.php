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
        Schema::create('guided_rate_systems', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('user_uuid');
            $table->enum('rate_type', ['shift', 'hourly']);
            $table->enum('nurse_type', ['RN', 'NAC'])->nullable();
            $table->integer('guided_rate');
            $table->string('care_duration')->default("2 hours(Live-out)");
            $table->mediumText('guided_rate_justification')->nullable();
            $table->timestamps();
            $table->foreign('user_uuid')
                ->references('uuid')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guided_rate_systems');
    }
};
