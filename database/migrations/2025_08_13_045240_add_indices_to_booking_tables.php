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
        // Add indices to booking_appts table for better performance
        Schema::table('booking_appts', function (Blueprint $table) {
            $table->index('status', 'idx_booking_appts_status');
            $table->index('created_at', 'idx_booking_appts_created_at');
            $table->index('start_date', 'idx_booking_appts_start_date');
            $table->index('user_uuid', 'idx_booking_appts_user_uuid');
            $table->index('health_worker_uuid', 'idx_booking_appts_health_worker_uuid');

            // Composite index for common query patterns
            $table->index(['user_uuid', 'status'], 'idx_booking_appts_user_status');
            $table->index(['user_uuid', 'created_at'], 'idx_booking_appts_user_created');
        });

        // Add indices to booking_appt_others table
        Schema::table('booking_appt_others', function (Blueprint $table) {
            $table->index('booking_appts_uuid', 'idx_booking_appt_others_booking_uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indices from booking_appts table
        Schema::table('booking_appts', function (Blueprint $table) {
            $table->dropIndex('idx_booking_appts_status');
            $table->dropIndex('idx_booking_appts_created_at');
            $table->dropIndex('idx_booking_appts_start_date');
            $table->dropIndex('idx_booking_appts_user_uuid');
            $table->dropIndex('idx_booking_appts_health_worker_uuid');
            $table->dropIndex('idx_booking_appts_user_status');
            $table->dropIndex('idx_booking_appts_user_created');
        });

        // Drop indices from booking_appt_others table
        Schema::table('booking_appt_others', function (Blueprint $table) {
            $table->dropIndex('idx_booking_appt_others_booking_uuid');
        });
    }
};
