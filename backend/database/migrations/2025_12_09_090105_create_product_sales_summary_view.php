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
        DB::statement("
            CREATE MATERIALIZED VIEW product_sales_summary AS
            SELECT 
                p.id as product_id,
                p.sku,
                p.name as product_name,
                p.category,
                p.subcategory,
                COUNT(DISTINCT li.order_id) as total_orders,
                SUM(li.quantity) as total_quantity_sold,
                SUM(li.line_total) as total_revenue,
                AVG(li.unit_price) as average_price,
                MAX(o.order_date) as last_sold_date,
                COUNT(DISTINCT o.customer_id) as unique_customers
            FROM products p
            LEFT JOIN line_items li ON p.id = li.product_id
            LEFT JOIN orders o ON li.order_id = o.id
            GROUP BY p.id, p.sku, p.name, p.category, p.subcategory
            WITH DATA;
        ");

        // Create indexes on materialized view
        DB::statement("CREATE UNIQUE INDEX idx_product_sales_summary_product_id ON product_sales_summary (product_id);");
        DB::statement("CREATE INDEX idx_product_sales_summary_category ON product_sales_summary (category);");
        DB::statement("CREATE INDEX idx_product_sales_summary_revenue ON product_sales_summary (total_revenue DESC);");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP MATERIALIZED VIEW IF EXISTS product_sales_summary;");
    }
};
