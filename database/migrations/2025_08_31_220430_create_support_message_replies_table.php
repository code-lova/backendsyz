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
        Schema::create('support_message_replies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('support_message_uuid'); // link to support_messages
            $table->longText('admin_reply');
            $table->string('reference');
            $table->timestamps();

            $table->foreign('support_message_uuid')
                ->references('uuid')
                ->on('support_messages')
                ->onDelete('cascade');

            $table->index('support_message_uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_message_replies');
    }
};
