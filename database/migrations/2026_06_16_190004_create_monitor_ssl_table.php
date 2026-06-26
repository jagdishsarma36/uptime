<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitor_ssl', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitor_id')->constrained()->cascadeOnDelete();
            $table->string('domain');
            $table->date('ssl_expiry_date')->nullable();
            $table->integer('days_left')->nullable();
            $table->string('issuer')->nullable();
            $table->string('serial_number')->nullable();
            $table->boolean('is_valid')->default(true);
            $table->text('error_message')->nullable();
            $table->timestamp('checked_at');

            $table->index(['monitor_id', 'checked_at']);
            $table->index(['domain', 'ssl_expiry_date']);
            $table->index('days_left');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitor_ssl');
    }
};
