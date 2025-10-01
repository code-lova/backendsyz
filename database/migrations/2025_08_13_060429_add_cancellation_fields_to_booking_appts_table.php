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
        Schema::table('booking_appts', function (Blueprint $table) {
            $table->string('booking_reference', 12)->unique()->nullable()->after('uuid');
            $table->text('reason_for_cancellation')->nullable()->after('status');
            $table->uuid('cancelled_by_user_uuid')->nullable()->after('reason_for_cancellation');
            $table->foreign('cancelled_by_user_uuid')
                ->references('uuid')
                ->on('users')
                ->onDelete('cascade');

            // Add indices for better query performance
            $table->index('booking_reference', 'idx_booking_appts_reference');
            $table->index('cancelled_by_user_uuid', 'idx_booking_appts_cancelled_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_appts', function (Blueprint $table) {
            $table->dropForeign(['cancelled_by_user_uuid']);
            // Drop indices
            $table->dropIndex('idx_booking_appts_reference');
            $table->dropIndex('idx_booking_appts_cancelled_by');
            
            $table->dropColumn(['booking_reference', 'reason_for_cancellation', 'cancelled_by_user_uuid']);
        });
    }
};
