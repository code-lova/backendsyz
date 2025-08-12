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
        Schema::create('booking_appt_others', function (Blueprint $table) {
            $table->id();
             $table->uuid()->uniqid();
            $table->uuid('booking_appts_uuid');
            $table->string('medical_services')->nullable();
            $table->string('other_extra_service')->nullable();
            $table->foreign('booking_appts_uuid')
                    ->references('uuid')
                    ->on('booking_appts')
                    ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_appt_others');
    }
};
