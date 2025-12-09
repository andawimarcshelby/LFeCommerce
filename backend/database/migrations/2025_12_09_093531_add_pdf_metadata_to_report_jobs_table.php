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
            $table->integer('page_count')->nullable()->after('file_size');
            $table->boolean('has_toc')->default(false)->after('page_count');
            $table->decimal('compression_ratio', 5, 2)->nullable()->after('has_toc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_jobs', function (Blueprint $table) {
            $table->dropColumn(['page_count', 'has_toc', 'compression_ratio']);
        });
    }
};
