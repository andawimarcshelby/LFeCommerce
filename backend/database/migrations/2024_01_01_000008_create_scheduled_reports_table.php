<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Schedule configuration
            $table->string('name');
            $table->string('description')->nullable();
            $table->enum('frequency', ['daily', 'weekly', 'monthly'])->default('daily');
            $table->time('scheduled_time')->default('09:00:00'); // Time to run
            $table->string('day_of_week')->nullable(); // For weekly: 'monday', 'tuesday', etc.
            $table->integer('day_of_month')->nullable(); // For monthly: 1-31
            $table->boolean('is_active')->default(true);

            // Report configuration
            $table->string('report_type'); // detail, summary, top_n, per_student
            $table->string('format'); // pdf, xlsx
            $table->json('filters')->nullable(); // Report filters

            // Execution tracking
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->integer('run_count')->default(0);
            $table->integer('success_count')->default(0);
            $table->integer('failure_count')->default(0);
            $table->text('last_error')->nullable();

            // Delivery options
            $table->boolean('send_email')->default(false);
            $table->string('email_recipients')->nullable(); // Comma-separated emails

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'is_active']);
            $table->index('next_run_at');
            $table->index(['frequency', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_reports');
    }
};
