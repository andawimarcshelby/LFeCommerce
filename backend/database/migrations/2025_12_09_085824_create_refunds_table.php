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
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->decimal('amount', 12, 2);
            $table->string('reason', 255);
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'processed'])->default('pending');
            $table->timestamp('refund_date')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('status');
            $table->index('refund_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
