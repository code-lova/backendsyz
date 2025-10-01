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
        Schema::create('booking_appts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('user_uuid');
            $table->uuid('health_worker_uuid')->nullable();
            $table->enum('requesting_for', ['Self', 'Someone'])->default('Self');
            $table->string('someone_name')->nullable();
            $table->string('someone_phone')->nullable();
            $table->string('someone_email')->nullable();
            $table->enum('care_duration', ['Hourly', 'Shift'])->default('Hourly');
            $table->integer('care_duration_value')->nullable();
            $table->enum('care_type', ['Live-out', 'Live-in'])->default('Live-out');
            //if client choose Live-in ask these questions
            $table->enum('accommodation', ['Yes', 'No'])->default('No');
            $table->enum('meal', ['Yes', 'No'])->default('No');
            $table->integer('num_of_meals')->default(0)->nullable();
            $table->longText('special_notes');
            $table->date('start_date');
            $table->date('end_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('start_time_period')->default('AM');
            $table->string('end_time_period')->default('AM');
            $table->enum('status', ['Pending', 'Processing', 'Confirmed', 'Ongoing', 'Done', 'Cancelled'])->default('Pending');
            $table->foreign('user_uuid')
                ->references('uuid')
                ->on('users')
                ->onDelete('cascade');
            $table->foreign('health_worker_uuid')
                ->references('uuid')
                ->on('users')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_appts');
    }
};
