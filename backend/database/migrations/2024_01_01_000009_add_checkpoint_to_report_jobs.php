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
        Schema::table('report_jobs', function (Blueprint $table) {
            // Add checkpoint data for job resumption
            $table->json('checkpoint_data')->nullable()->after('current_section');
            $table->unsignedSmallInteger('retry_count')->default(0)->after('checkpoint_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_jobs', function (Blueprint $table) {
            $table->dropColumn(['checkpoint_data', 'retry_count']);
        });
    }
};
