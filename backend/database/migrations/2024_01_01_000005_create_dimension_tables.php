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
        // Terms table
        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50); // Fall 2024, Spring 2025
            $table->string('term_code', 20)->unique(); // 2024-FALL, 2025-SPRING
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Courses table
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('course_code', 20); // CS101, MATH201
            $table->string('course_name', 200);
            $table->foreignId('term_id')->constrained('terms')->onDelete('cascade');
            $table->string('department', 100)->nullable();
            $table->integer('credits')->default(3);
            $table->integer('enrollment_count')->default(0);
            $table->timestamps();

            $table->index(['term_id', 'department']);
        });

        // Students table
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_number', 50)->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 200)->unique();
            $table->string('program', 100)->nullable(); // Computer Science, Engineering
            $table->integer('year_level')->nullable(); // 1, 2, 3, 4
            $table->string('enrollment_status', 20)->default('active');
            $table->timestamps();

            $table->index('program');
            $table->index('enrollment_status');
        });

        // Instructors table
        Schema::create('instructors', function (Blueprint $table) {
            $table->id();
            $table->string('employee_number', 50)->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 200)->unique();
            $table->string('department', 100)->nullable();
            $table->string('title', 100)->nullable(); // Professor, Associate Professor
            $table->timestamps();

            $table->index('department');
        });

        // Assignments table
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->string('title', 200);
            $table->string('assignment_type', 30); // homework, quiz, exam, project
            $table->decimal('max_score', 5, 2);
            $table->timestampTz('due_date')->nullable();
            $table->boolean('allows_late')->default(false);
            $table->timestamps();

            $table->index(['course_id', 'due_date']);
        });

        // Course enrollments table
        Schema::create('course_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->foreignId('term_id')->constrained('terms')->onDelete('cascade');
            $table->timestampTz('enrolled_at')->useCurrent();
            $table->timestamps();

            $table->unique(['student_id', 'course_id', 'term_id']);
            $table->index(['course_id', 'term_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_enrollments');
        Schema::dropIfExists('assignments');
        Schema::dropIfExists('instructors');
        Schema::dropIfExists('students');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('terms');
    }
};
