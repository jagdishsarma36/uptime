<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('check_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitor_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['up', 'down']);
            $table->integer('http_code')->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->text('message')->nullable();
            $table->string('checked_from')->nullable();
            $table->timestamp('checked_at');

            $table->index(['monitor_id', 'checked_at']);
            $table->index('checked_at');
            $table->index(['monitor_id', 'status', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('check_results');
    }
};
