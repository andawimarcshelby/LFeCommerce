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
        Schema::create('orders_daily_summary', function (Blueprint $table) {
            $table->id();
            $table->date('summary_date');
            $table->foreignId('region_id')->constrained()->onDelete('cascade');
            $table->integer('total_orders')->default(0);
            $table->integer('completed_orders')->default(0);
            $table->integer('cancelled_orders')->default(0);
            $table->decimal('total_revenue', 14, 2)->default(0);
            $table->decimal('total_tax', 12, 2)->default(0);
            $table->decimal('total_shipping', 12, 2)->default(0);
            $table->decimal('total_discounts', 12, 2)->default(0);
            $table->decimal('average_order_value', 10, 2)->default(0);
            $table->integer('unique_customers')->default(0);
            $table->timestamps();

            $table->unique(['summary_date', 'region_id']);
            $table->index('summary_date');
            $table->index('region_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders_daily_summary');
    }
};
