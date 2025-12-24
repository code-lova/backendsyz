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
        Schema::create('service_flyers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image_url'); // Cloudinary URL
            $table->string('image_public_id'); // Cloudinary public ID for deletion
            $table->enum('target_audience', ['client', 'healthworker', 'both'])->default('both');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0); // For custom ordering
            $table->uuid('created_by'); // Admin who created it
            $table->timestamps();

            // Index for better performance
            $table->index(['target_audience', 'is_active', 'sort_order']);
            $table->foreign('created_by')->references('uuid')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_flyers');
    }
};
