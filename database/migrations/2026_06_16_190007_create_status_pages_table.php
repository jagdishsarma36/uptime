<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('status_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('slug')->unique();
            $table->string('public_key', 64)->unique();
            $table->enum('theme', ['light', 'dark'])->default('light');
            $table->string('custom_domain')->nullable()->unique();
            $table->string('password')->nullable();
            $table->boolean('is_published')->default(true);
            $table->json('custom_css')->nullable();
            $table->json('custom_logo')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('public_key');
            $table->index('custom_domain');
        });

        Schema::create('status_page_monitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('status_page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('monitor_id')->constrained()->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->unique(['status_page_id', 'monitor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('status_page_monitors');
        Schema::dropIfExists('status_pages');
    }
};
