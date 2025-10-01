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
        Schema::create('guided_rate_service_types', function (Blueprint $table) {
            $table->id();
            $table->uuid()->uniqid();
            $table->uuid('grs_uuid');
            $table->string('service_type');
            $table->foreign('grs_uuid')
                    ->references('uuid')
                    ->on('guided_rate_systems')
                    ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guided_rate_service_types');
    }
};
