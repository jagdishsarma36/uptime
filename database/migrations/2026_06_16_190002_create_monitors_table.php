<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('group_id')->nullable()->constrained('monitor_groups')->nullOnDelete();
            $table->string('name');
            $table->string('url');
            $table->enum('type', ['http', 'https', 'tcp', 'dns', 'keyword'])->default('https');
            $table->enum('method', ['GET', 'POST', 'PUT', 'HEAD'])->default('GET');
            $table->json('headers')->nullable();
            $table->text('body')->nullable();
            $table->integer('expected_status_code')->nullable()->default(200);
            $table->string('expected_keyword')->nullable();
            $table->string('tcp_host')->nullable();
            $table->integer('tcp_port')->nullable();
            $table->integer('check_interval_seconds')->default(300);
            $table->enum('status', ['unknown', 'up', 'down', 'paused'])->default('unknown');
            $table->timestamp('last_status_change')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('next_check_at')->nullable();
            $table->integer('last_http_code')->nullable();
            $table->integer('last_response_time_ms')->nullable();
            $table->text('last_error_message')->nullable();
            $table->integer('consecutive_failures')->default(0);
            $table->boolean('is_paused')->default(false);
            $table->boolean('ssl_enabled')->default(true);
            $table->boolean('domain_enabled')->default(true);
            $table->integer('alert_threshold_seconds')->default(300);
            $table->boolean('alert_on_down')->default(true);
            $table->boolean('alert_on_up')->default(true);
            $table->boolean('alert_on_ssl_expiry')->default(true);
            $table->boolean('alert_on_domain_expiry')->default(true);
            $table->integer('ssl_alert_threshold_days')->default(20);
            $table->integer('domain_alert_threshold_days')->default(20);
            $table->string('slug', 64)->unique();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'status']);
            $table->index(['team_id', 'next_check_at', 'is_paused']);
            $table->index(['team_id', 'group_id']);
            $table->index(['status', 'next_check_at']);
            $table->index('next_check_at');
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitors');
    }
};
