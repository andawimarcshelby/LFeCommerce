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
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('tracking_number', 100)->unique();
            $table->string('carrier', 100);
            $table->enum('status', ['pending', 'shipped', 'in_transit', 'delivered', 'failed'])->default('pending');
            $table->timestamp('shipped_date')->nullable();
            $table->timestamp('estimated_delivery')->nullable();
            $table->timestamp('delivered_date')->nullable();
            $table->text('delivery_notes')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('tracking_number');
            $table->index('status');
            $table->index('shipped_date');
            $table->index('delivered_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
