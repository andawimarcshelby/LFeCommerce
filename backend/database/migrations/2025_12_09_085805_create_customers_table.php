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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('email', 255)->unique();
            $table->string('name', 255);
            $table->string('phone', 50)->nullable();
            $table->foreignId('region_id')->constrained()->onDelete('cascade');
            $table->enum('account_type', ['individual', 'business', 'premium'])->default('individual');
            $table->string('company_name', 255)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->decimal('lifetime_value', 12, 2)->default(0);
            $table->integer('total_orders')->default(0);
            $table->timestamp('last_order_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['region_id', 'account_type']);
            $table->index('email');
            $table->index('last_order_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
