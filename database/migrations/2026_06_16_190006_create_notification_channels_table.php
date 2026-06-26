<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['email', 'slack', 'webhook', 'telegram', 'sms']);
            $table->json('config');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'type']);
        });

        Schema::create('notification_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('notification_channel_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel_type', 50);
            $table->enum('status', ['sent', 'failed', 'throttled']);
            $table->text('message')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at');

            $table->index(['monitor_id', 'sent_at']);
            $table->index('sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_log');
        Schema::dropIfExists('notification_channels');
    }
};
