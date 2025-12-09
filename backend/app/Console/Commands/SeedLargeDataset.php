<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class SeedLargeDataset extends Command
{
    protected $signature = 'db:seed-large {--quick : Seed smaller dataset for quick testing}';
    protected $description = 'Seed database with 10M+ orders for high-volume reporting';

    private $faker;
    private $regions = [];
    private $customers = [];
    private $products = [];

    public function handle()
    {
        $this->faker = Faker::create();
        $quick = $this->option('quick');

        $this->info('🚀 Starting large dataset seeding...');
        $this->info($quick ? '⚡ Quick mode: Smaller dataset' : '📊 Full mode: 10M+ orders');

        // Seed dimensions first
        $this->seedRegions();
        $this->seedProducts($quick ? 1000 : 100000);
        $this->seedCustomers($quick ? 10000 : 2000000);

        // Seed facts
        $this->seedOrders($quick ? 100000 : 10000000);

        $this->info('✅ Seeding completed successfully!');
    }

    private function seedRegions()
    {
        $this->info('📍 Seeding regions...');

        $regions = [
            ['name' => 'North America - East', 'country' => 'USA', 'timezone' => 'America/New_York'],
            ['name' => 'North America - West', 'country' => 'USA', 'timezone' => 'America/Los_Angeles'],
            ['name' => 'North America - Central', 'country' => 'USA', 'timezone' => 'America/Chicago'],
            ['name' => 'Europe - UK', 'country' => 'United Kingdom', 'timezone' => 'Europe/London'],
            ['name' => 'Europe - Central', 'country' => 'Germany', 'timezone' => 'Europe/Berlin'],
            ['name' => 'Asia - East', 'country' => 'Japan', 'timezone' => 'Asia/Tokyo'],
            ['name' => 'Asia - Southeast', 'country' => 'Singapore', 'timezone' => 'Asia/Singapore'],
            ['name' => 'Australia', 'country' => 'Australia', 'timezone' => 'Australia/Sydney'],
        ];

        foreach ($regions as $region) {
            $region['is_active'] = true;
            $region['created_at'] = now();
            $region['updated_at'] = now();
            DB::table('regions')->insert($region);
        }

        $this->regions = DB::table('regions')->pluck('id')->toArray();
        $this->info('✓ Seeded ' . count($this->regions) . ' regions');
    }

    private function seedProducts($count)
    {
        $this->info("📦 Seeding {$count} products...");

        $categories = [
            'Electronics' => ['Smartphones', 'Laptops', 'Tablets', 'Accessories'],
            'Clothing' => ['Men', 'Women', 'Kids', 'Accessories'],
            'Home & Garden' => ['Furniture', 'Decor', 'Kitchen', 'Tools'],
            'Sports' => ['Equipment', 'Apparel', 'Footwear', 'Accessories'],
            'Books' => ['Fiction', 'Non-Fiction', 'Educational', 'Comics'],
        ];

        $chunkSize = 5000;
        $bar = $this->output->createProgressBar($count);

        for ($i = 0; $i < $count; $i += $chunkSize) {
            $batch = [];
            $batchSize = min($chunkSize, $count - $i);

            for ($j = 0; $j < $batchSize; $j++) {
                $category = $this->faker->randomElement(array_keys($categories));
                $subcategory = $this->faker->randomElement($categories[$category]);

                $batch[] = [
                    'sku' => 'SKU-' . str_pad($i + $j + 1, 8, '0', STR_PAD_LEFT),
                    'name' => $this->faker->words(3, true),
                    'description' => $this->faker->sentence(),
                    'category' => $category,
                    'subcategory' => $subcategory,
                    'price' => $this->faker->randomFloat(2, 5, 500),
                    'cost' => $this->faker->randomFloat(2, 2, 250),
                    'stock_quantity' => $this->faker->numberBetween(0, 1000),
                    'weight' => $this->faker->randomFloat(2, 0.1, 50),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::table('products')->insert($batch);
            $bar->advance($batchSize);
        }

        $bar->finish();
        $this->newLine();

        $this->products = DB::table('products')->pluck('id')->toArray();
        $this->info('✓ Seeded ' . count($this->products) . ' products');
    }

    private function seedCustomers($count)
    {
        $this->info("👥 Seeding {$count} customers...");

        $chunkSize = 5000;
        $bar = $this->output->createProgressBar($count);

        for ($i = 0; $i < $count; $i += $chunkSize) {
            $batch = [];
            $batchSize = min($chunkSize, $count - $i);

            for ($j = 0; $j < $batchSize; $j++) {
                $accountType = $this->faker->randomElement(['individual', 'individual', 'individual', 'business', 'premium']);

                $batch[] = [
                    'email' => 'customer' . ($i + $j + 1) . '@example.com',
                    'name' => $this->faker->name(),
                    'phone' => $this->faker->phoneNumber(),
                    'region_id' => $this->faker->randomElement($this->regions),
                    'account_type' => $accountType,
                    'company_name' => $accountType === 'business' ? $this->faker->company() : null,
                    'address' => $this->faker->streetAddress(),
                    'city' => $this->faker->city(),
                    'state' => $this->faker->state(),
                    'postal_code' => $this->faker->postcode(),
                    'lifetime_value' => 0,
                    'total_orders' => 0,
                    'last_order_at' => null,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::table('customers')->insert($batch);
            $bar->advance($batchSize);
        }

        $bar->finish();
        $this->newLine();

        $this->customers = DB::table('customers')->pluck('id')->toArray();
        $this->info('✓ Seeded ' . count($this->customers) . ' customers');
    }

    private function seedOrders($count)
    {
        $this->info("🛒 Seeding {$count} orders with line items...");

        $chunkSize = 2000;
        $bar = $this->output->createProgressBar($count);

        $statuses = ['pending', 'processing', 'completed', 'completed', 'completed', 'completed', 'cancelled'];
        $paymentMethods = ['credit_card', 'debit_card', 'paypal', 'bank_transfer'];

        // Distribute orders across 2023-2025
        $startDate = strtotime('2023-01-01');
        $endDate = strtotime('2025-12-31');

        for ($i = 0; $i < $count; $i += $chunkSize) {
            $ordersBatch = [];
            $lineItemsBatch = [];
            $batchSize = min($chunkSize, $count - $i);

            for ($j = 0; $j < $batchSize; $j++) {
                $orderNumber = 'ORD-' . str_pad($i + $j + 1, 10, '0', STR_PAD_LEFT);
                $customerId = $this->faker->randomElement($this->customers);
                $regionId = $this->faker->randomElement($this->regions);
                $orderDate = date('Y-m-d H:i:s', $this->faker->numberBetween($startDate, $endDate));
                $status = $this->faker->randomElement($statuses);

                // Generate line items
                $numItems = $this->faker->numberBetween(1, 8);
                $subtotal = 0;

                $tempOrderId = $i + $j + 1; // Temporary ID for line items

                for ($k = 0; $k < $numItems; $k++) {
                    $productId = $this->faker->randomElement($this->products);
                    $quantity = $this->faker->numberBetween(1, 5);
                    $unitPrice = $this->faker->randomFloat(2, 10, 300);
                    $discount = $this->faker->randomFloat(2, 0, $unitPrice * $quantity * 0.2);
                    $tax = ($unitPrice * $quantity - $discount) * 0.08;
                    $lineTotal = $unitPrice * $quantity - $discount + $tax;

                    $subtotal += $lineTotal;

                    $lineItemsBatch[] = [
                        'order_id' => $tempOrderId,
                        'product_id' => $productId,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'discount' => $discount,
                        'tax' => $tax,
                        'line_total' => $lineTotal,
                        'created_at' => $orderDate,
                        'updated_at' => $orderDate,
                    ];
                }

                $tax = $subtotal * 0.08;
                $shippingCost = $this->faker->randomFloat(2, 0, 25);
                $discount = $this->faker->randomFloat(2, 0, $subtotal * 0.1);
                $totalAmount = $subtotal + $tax + $shippingCost - $discount;

                $ordersBatch[] = [
                    'order_number' => $orderNumber,
                    'customer_id' => $customerId,
                    'region_id' => $regionId,
                    'order_date' => $orderDate,
                    'status' => $status,
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'shipping_cost' => $shippingCost,
                    'discount' => $discount,
                    'total_amount' => $totalAmount,
                    'payment_method' => $this->faker->randomElement($paymentMethods),
                    'payment_status' => $status === 'completed' ? 'paid' : 'pending',
                    'shipping_address' => $this->faker->address(),
                    'billing_address' => $this->faker->address(),
                    'notes' => $this->faker->optional()->sentence(),
                    'created_at' => $orderDate,
                    'updated_at' => $orderDate,
                ];
            }

            // Insert orders
            DB::table('orders')->insert($ordersBatch);

            // Insert line items
            if (!empty($lineItemsBatch)) {
                DB::table('line_items')->insert($lineItemsBatch);
            }

            $bar->advance($batchSize);
        }

        $bar->finish();
        $this->newLine();

        $this->info('✓ Seeded ' . $count . ' orders with line items');
    }
}
