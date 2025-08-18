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
        Schema::create('client_support_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('ticket_uuid'); // link to support_tickets
            $table->uuid('sender_uuid'); // user who sent the message (client/admin)
            $table->enum('sender_type', ['client', 'admin']);
            $table->longText('message');
            $table->timestamps();

            $table->foreign('ticket_uuid')
                ->references('uuid')
                ->on('client_support_tickets')
                ->onDelete('cascade');

            $table->foreign('sender_uuid')
                ->references('uuid')
                ->on('users')
                ->onDelete('cascade');

            $table->index('ticket_uuid');
            $table->index('sender_uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_support_messages');
    }
};
