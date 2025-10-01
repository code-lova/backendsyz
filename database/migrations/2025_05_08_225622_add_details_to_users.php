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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->after('email');
            $table->enum('role', ['admin', 'healthworker', 'client'])->default('client')->after('phone');
            $table->enum('practitioner', ['doctor', 'nurse', 'physician_assistant'])->nullable()->after('role');
            $table->string('gender')->after('password')->nullable();
            $table->string('date_of_birth')->after('gender')->nullable();
            $table->string('image')->after('date_of_birth')->nullable();
            $table->string('address')->after('image')->nullable();
            $table->string('religion')->after('address')->nullable();
            $table->enum('is_active', ['0', '1'])->default(1)->after('religion');
            $table->timestamp('last_logged_in')->nullable()->after('email_verified_at');
            $table->decimal('latitude', 10, 8)->after('is_active')->nullable();  // precise location
            $table->decimal('longitude', 11, 8)->after('latitude')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
