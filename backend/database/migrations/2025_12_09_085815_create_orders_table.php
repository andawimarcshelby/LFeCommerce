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
        // Create orders table with partitioning
        DB::statement("
            CREATE TABLE orders (
                id BIGSERIAL,
                order_number VARCHAR(50) UNIQUE NOT NULL,
                customer_id BIGINT NOT NULL,
                region_id BIGINT NOT NULL,
                order_date TIMESTAMPTZ NOT NULL,
                status VARCHAR(50) NOT NULL DEFAULT 'pending',
                subtotal DECIMAL(12, 2) NOT NULL DEFAULT 0,
                tax DECIMAL(12, 2) NOT NULL DEFAULT 0,
                shipping_cost DECIMAL(12, 2) NOT NULL DEFAULT 0,
                discount DECIMAL(12, 2) NOT NULL DEFAULT 0,
                total_amount DECIMAL(12, 2) NOT NULL DEFAULT 0,
                payment_method VARCHAR(50),
                payment_status VARCHAR(50) DEFAULT 'pending',
                shipping_address TEXT,
                billing_address TEXT,
                notes TEXT,
                created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id, order_date)
            ) PARTITION BY RANGE (order_date);
        ");

        // Create partitions for 2023, 2024, 2025
        $years = [2023, 2024, 2025];
        foreach ($years as $year) {
            for ($month = 1; $month <= 12; $month++) {
                $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
                $nextMonth = $month == 12 ? 1 : $month + 1;
                $nextYear = $month == 12 ? $year + 1 : $year;
                $nextMonthStr = str_pad($nextMonth, 2, '0', STR_PAD_LEFT);

                DB::statement("
                    CREATE TABLE orders_{$year}_{$monthStr} PARTITION OF orders
                    FOR VALUES FROM ('{$year}-{$monthStr}-01') TO ('{$nextYear}-{$nextMonthStr}-01');
                ");
            }
        }

        // Create indexes
        DB::statement("CREATE INDEX idx_orders_customer_id ON orders (customer_id);");
        DB::statement("CREATE INDEX idx_orders_region_id ON orders (region_id);");
        DB::statement("CREATE INDEX idx_orders_order_date_desc ON orders (order_date DESC);");
        DB::statement("CREATE INDEX idx_orders_status ON orders (status);");
        DB::statement("CREATE INDEX idx_orders_region_date ON orders (region_id, order_date);");
        DB::statement("CREATE INDEX idx_orders_customer_date ON orders (customer_id, order_date);");

        // Create partial index for completed orders
        DB::statement("CREATE INDEX idx_orders_completed ON orders (order_date) WHERE status = 'completed';");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
