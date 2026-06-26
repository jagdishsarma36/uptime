<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitor_domain', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitor_id')->constrained()->cascadeOnDelete();
            $table->string('domain');
            $table->date('expiry_date')->nullable();
            $table->integer('days_left')->nullable();
            $table->string('registrar')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('checked_at');

            $table->index(['monitor_id', 'checked_at']);
            $table->index(['domain', 'expiry_date']);
            $table->index('days_left');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitor_domain');
    }
};
