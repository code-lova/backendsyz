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
        Schema::create('booking_recurrences', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('booking_appts_uuid');
            
            // Recurrence settings
            $table->enum('is_recurring', ['Yes', 'No'])->default('No');
            $table->enum('recurrence_type', ['Daily', 'Weekly', 'Monthly'])->nullable();
            $table->json('recurrence_days')->nullable(); // For weekly: ["Monday", "Tuesday", etc.]
            $table->enum('recurrence_end_type', ['date', 'occurrences'])->nullable();
            $table->date('recurrence_end_date')->nullable();
            $table->unsignedInteger('recurrence_occurrences')->nullable();
            
            $table->timestamps();
            
            // Foreign key constraint
            $table->foreign('booking_appts_uuid')
                  ->references('uuid')
                  ->on('booking_appts')
                  ->onDelete('cascade');
                  
            // Index for faster queries
            $table->index('booking_appts_uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_recurrences');
    }
};
