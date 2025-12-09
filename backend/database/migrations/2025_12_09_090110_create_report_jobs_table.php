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
        Schema::create('report_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('report_type', 100); // detail, summary, top-n, exceptions, per-entity
            $table->enum('format', ['pdf', 'xlsx'])->default('pdf');
            $table->enum('status', ['queued', 'running', 'completed', 'failed'])->default('queued');
            $table->json('filters')->nullable(); // Serialized filter parameters
            $table->bigInteger('total_rows')->nullable();
            $table->bigInteger('processed_rows')->default(0);
            $table->string('current_section', 255)->nullable();
            $table->decimal('percent', 5, 2)->default(0);
            $table->string('file_path', 500)->nullable();
            $table->bigInteger('file_size')->nullable(); // in bytes
            $table->string('signed_url', 1000)->nullable();
            $table->timestamp('url_expires_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
            $table->index(['user_id', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_jobs');
    }
};
