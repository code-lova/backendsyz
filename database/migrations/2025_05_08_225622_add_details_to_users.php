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
            $table->enum('role', ['admin', 'nurse', 'client'])->default('client')->after('phone');
            $table->string('gender')->after('password')->nullable();
            $table->string('date_of_birth')->after('gender')->nullable();
            $table->string('image')->after('date_of_birth')->nullable();
            $table->string('place_of_birth')->after('image')->nullable();
            $table->string('blood_group')->after('place_of_birth')->nullable();
            $table->string('genotype')->after('blood_group')->nullable();
            $table->string('address')->after('genotype')->nullable();
            $table->string('religion')->after('address')->nullable();
            $table->string('nationality')->after('religion')->nullable();
            $table->integer('weight')->after('nationality')->nullable();
            $table->integer('height')->after('weight')->nullable();
            $table->enum('is_active', ['0', '1'])->default(1)->after('height');

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
