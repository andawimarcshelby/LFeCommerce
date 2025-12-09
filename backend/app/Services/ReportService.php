<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Region;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class ReportService
{
    /**
     * Generate detail report with streaming support
     */
    public function detailReport(array $filters, int $page = 1, int $perPage = 100)
    {
        $query = $this->buildOrderQuery($filters);

        return [
            'data' => $query->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get(),
            'total' => $query->count(),
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    /**
     * Generate summary report grouped by specified dimension
     */
    public function summaryReport(array $filters, string $groupBy = 'date')
    {
        $query = DB::table('orders')
            ->select([
                DB::raw($this->getGroupByExpression($groupBy) . ' as group_key'),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(total_amount) as total_revenue'),
                DB::raw('AVG(total_amount) as average_order_value'),
                DB::raw('SUM(tax) as total_tax'),
                DB::raw('SUM(shipping_cost) as total_shipping'),
            ]);

        $this->applyFilters($query, $filters);

        return $query->groupBy('group_key')
            ->orderBy('group_key', 'desc')
            ->get();
    }

    /**
     * Generate Top-N report
     */
    public function topNReport(array $filters, string $type = 'customers', int $limit = 100)
    {
        switch ($type) {
            case 'customers':
                return $this->topCustomers($filters, $limit);
            case 'products':
                return $this->topProducts($filters, $limit);
            case 'regions':
                return $this->topRegions($filters, $limit);
            default:
                throw new \InvalidArgumentException("Invalid top-N type: {$type}");
        }
    }

    /**
     * Generate exceptions report (failed orders, refunds, etc.)
     */
    public function exceptionsReport(array $filters, string $type = 'failed_orders')
    {
        switch ($type) {
            case 'failed_orders':
                return $this->failedOrders($filters);
            case 'refunds':
                return $this->refunds($filters);
            case 'cancelled_orders':
                return $this->cancelledOrders($filters);
            default:
                throw new \InvalidArgumentException("Invalid exception type: {$type}");
        }
    }

    /**
     * Stream orders for large exports
     */
    public function streamOrders(array $filters, callable $callback, int $chunkSize = 10000)
    {
        $query = $this->buildOrderQuery($filters);

        $query->chunk($chunkSize, function ($orders) use ($callback) {
            foreach ($orders as $order) {
                $callback($order);
            }
        });
    }

    /**
     * Build base order query with filters
     */
    public function buildOrderQuery(array $filters): Builder
    {
        $query = Order::query()
            ->with(['customer', 'region', 'lineItems.product']);

        $this->applyFilters($query, $filters);

        return $query;
    }

    /**
     * Apply filters to query
     */
    private function applyFilters($query, array $filters): void
    {
        // Date range filter
        if (!empty($filters['date_from'])) {
            $query->where('order_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('order_date', '<=', $filters['date_to']);
        }

        // Region filter
        if (!empty($filters['region_id'])) {
            $query->where('region_id', $filters['region_id']);
        }

        // Customer filter
        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        // Status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Payment method filter
        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        // Amount range filter
        if (!empty($filters['amount_min'])) {
            $query->where('total_amount', '>=', $filters['amount_min']);
        }
        if (!empty($filters['amount_max'])) {
            $query->where('total_amount', '<=', $filters['amount_max']);
        }
    }

    /**
     * Get group by expression based on dimension
     */
    private function getGroupByExpression(string $groupBy): string
    {
        switch ($groupBy) {
            case 'date':
                return "DATE(order_date)";
            case 'month':
                return "DATE_TRUNC('month', order_date)";
            case 'region':
                return "region_id";
            case 'status':
                return "status";
            case 'payment_method':
                return "payment_method";
            default:
                return "DATE(order_date)";
        }
    }

    /**
     * Top customers by revenue
     */
    private function topCustomers(array $filters, int $limit)
    {
        $query = DB::table('orders')
            ->join('customers', 'orders.customer_id', '=', 'customers.id')
            ->select([
                'customers.id',
                'customers.name',
                'customers.email',
                'customers.account_type',
                DB::raw('COUNT(orders.id) as total_orders'),
                DB::raw('SUM(orders.total_amount) as total_revenue'),
                DB::raw('AVG(orders.total_amount) as average_order_value'),
            ]);

        $this->applyFilters($query, $filters);

        return $query->groupBy('customers.id', 'customers.name', 'customers.email', 'customers.account_type')
            ->orderBy('total_revenue', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Top products by sales
     */
    private function topProducts(array $filters, int $limit)
    {
        $query = DB::table('line_items')
            ->join('products', 'line_items.product_id', '=', 'products.id')
            ->join('orders', 'line_items.order_id', '=', 'orders.id')
            ->select([
                'products.id',
                'products.sku',
                'products.name',
                'products.category',
                DB::raw('SUM(line_items.quantity) as total_quantity'),
                DB::raw('SUM(line_items.line_total) as total_revenue'),
                DB::raw('COUNT(DISTINCT orders.id) as total_orders'),
            ]);

        $this->applyFilters($query, $filters);

        return $query->groupBy('products.id', 'products.sku', 'products.name', 'products.category')
            ->orderBy('total_revenue', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Top regions by revenue
     */
    private function topRegions(array $filters, int $limit)
    {
        $query = DB::table('orders')
            ->join('regions', 'orders.region_id', '=', 'regions.id')
            ->select([
                'regions.id',
                'regions.name',
                'regions.country',
                DB::raw('COUNT(orders.id) as total_orders'),
                DB::raw('SUM(orders.total_amount) as total_revenue'),
                DB::raw('AVG(orders.total_amount) as average_order_value'),
            ]);

        $this->applyFilters($query, $filters);

        return $query->groupBy('regions.id', 'regions.name', 'regions.country')
            ->orderBy('total_revenue', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Failed/cancelled orders
     */
    private function failedOrders(array $filters)
    {
        $filters['status'] = 'failed';
        return $this->buildOrderQuery($filters)->get();
    }

    /**
     * Refunds report
     */
    private function refunds(array $filters)
    {
        $query = DB::table('refunds')
            ->join('orders', 'refunds.order_id', '=', 'orders.id')
            ->join('customers', 'orders.customer_id', '=', 'customers.id')
            ->select([
                'refunds.*',
                'orders.order_number',
                'customers.name as customer_name',
                'customers.email as customer_email',
            ]);

        if (!empty($filters['date_from'])) {
            $query->where('refunds.refund_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('refunds.refund_date', '<=', $filters['date_to']);
        }

        return $query->orderBy('refunds.refund_date', 'desc')->get();
    }

    /**
     * Cancelled orders
     */
    private function cancelledOrders(array $filters)
    {
        $filters['status'] = 'cancelled';
        return $this->buildOrderQuery($filters)->get();
    }

    /**
     * Estimate total rows for a report
     */
    public function estimateRows(array $filters): int
    {
        $query = DB::table('orders');
        $this->applyFilters($query, $filters);
        return $query->count();
    }
}
