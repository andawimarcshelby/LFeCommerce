<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Grading audits table (not partitioned - smaller volume)
        Schema::create('grading_audits', function (Blueprint $table) {
            $table->id();
            $table->string('action_type', 50); // grade_assigned, grade_changed, comment_added, auto_graded
            $table->unsignedBigInteger('submission_id');
            $table->unsignedBigInteger('actor_id'); // instructor or system ID
            $table->string('actor_type', 20); // instructor, auto_grader, system
            $table->decimal('old_score', 5, 2)->nullable();
            $table->decimal('new_score', 5, 2)->nullable();
            $table->text('comment')->nullable();
            $table->timestampTz('occurred_at');
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['submission_id', 'occurred_at']);
            $table->index(['actor_id', 'occurred_at']);
            $table->index('action_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grading_audits');
    }
};
