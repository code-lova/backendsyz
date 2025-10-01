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
        // Add indexes to users table for login optimization
        Schema::table('users', function (Blueprint $table) {
            // Add index on role for dashboard queries
            $table->index('role');
            
            // Add index on is_active for blocked user checks
            $table->index('is_active');
            
            // Add composite index for email verification queries
            $table->index(['email', 'email_verification_code']);
            
            // Add index on email_verified_at for verified health worker queries
            $table->index('email_verified_at');
            
            // Add composite index for role-based queries with verification status
            $table->index(['role', 'email_verified_at']);
        });

        // Add indexes to booking_appts table for dashboard optimization
        Schema::table('booking_appts', function (Blueprint $table) {
            // Add index on user_uuid for user-specific queries
            $table->index('user_uuid');
            
            // Add index on created_at for monthly activity queries
            $table->index('created_at');
            
            // Add index on start_date for upcoming appointment queries
            $table->index('start_date');
            
            // Add index on status for status-based filtering
            $table->index('status');
            
            // Add composite indexes for common query patterns
            $table->index(['user_uuid', 'created_at']); // Monthly activity
            $table->index(['user_uuid', 'start_date']); // Next appointment
            $table->index(['user_uuid', 'status']); // Status filtering
            
            // Add composite index for complex date/status queries
            $table->index(['user_uuid', 'start_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['email', 'email_verification_code']);
            $table->dropIndex(['email_verified_at']);
            $table->dropIndex(['role', 'email_verified_at']);
        });

        // Remove indexes from booking_appts table
        Schema::table('booking_appts', function (Blueprint $table) {
            $table->dropIndex(['user_uuid']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['start_date']);
            $table->dropIndex(['status']);
            $table->dropIndex(['user_uuid', 'created_at']);
            $table->dropIndex(['user_uuid', 'start_date']);
            $table->dropIndex(['user_uuid', 'status']);
            $table->dropIndex(['user_uuid', 'start_date', 'status']);
        });
    }
};
