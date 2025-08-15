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
        Schema::create('healthworker_reviews', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->uuid('booking_appt_uuid');
            $table->uuid('client_uuid');
            $table->uuid('healthworker_uuid');
            $table->unsignedTinyInteger('rating'); // 1-5
            $table->text('review')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('booking_appt_uuid')
                ->references('uuid')->on('booking_appts')
                ->onDelete('cascade');
            $table->foreign('client_uuid')
                ->references('uuid')->on('users')
                ->onDelete('cascade');
            $table->foreign('healthworker_uuid')
                ->references('uuid')->on('users')
                ->onDelete('cascade');

            $table->index('booking_appt_uuid');
            $table->index('client_uuid');
            $table->index('healthworker_uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('healthworker_reviews');
    }
};
