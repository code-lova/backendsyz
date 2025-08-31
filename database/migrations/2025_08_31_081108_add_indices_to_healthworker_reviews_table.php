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
        Schema::table('healthworker_reviews', function (Blueprint $table) {
            // Add indices for better query performance
            $table->index('healthworker_uuid', 'idx_healthworker_reviews_healthworker_uuid');
            $table->index('client_uuid', 'idx_healthworker_reviews_client_uuid');
            $table->index('booking_appt_uuid', 'idx_healthworker_reviews_booking_appt_uuid');
            $table->index('rating', 'idx_healthworker_reviews_rating');
            $table->index('reviewed_at', 'idx_healthworker_reviews_reviewed_at');
            
            // Composite index for common filtering combinations
            $table->index(['healthworker_uuid', 'rating'], 'idx_healthworker_reviews_hw_rating');
            $table->index(['reviewed_at', 'rating'], 'idx_healthworker_reviews_date_rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('healthworker_reviews', function (Blueprint $table) {
            // Drop the indices
            $table->dropIndex('idx_healthworker_reviews_healthworker_uuid');
            $table->dropIndex('idx_healthworker_reviews_client_uuid');
            $table->dropIndex('idx_healthworker_reviews_booking_appt_uuid');
            $table->dropIndex('idx_healthworker_reviews_rating');
            $table->dropIndex('idx_healthworker_reviews_reviewed_at');
            $table->dropIndex('idx_healthworker_reviews_hw_rating');
            $table->dropIndex('idx_healthworker_reviews_date_rating');
        });
    }
};
