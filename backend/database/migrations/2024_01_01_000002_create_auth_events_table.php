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
        // Create partitioned auth_events table
        DB::statement("
            CREATE TABLE auth_events (
                id BIGSERIAL,
                event_type VARCHAR(30) NOT NULL,
                user_id BIGINT NOT NULL,
                user_type VARCHAR(20) NOT NULL,
                ip_address INET,
                user_agent TEXT,
                session_id VARCHAR(100),
                occurred_at TIMESTAMPTZ NOT NULL,
                created_at TIMESTAMPTZ DEFAULT NOW(),
                PRIMARY KEY (id, occurred_at)
            ) PARTITION BY RANGE (occurred_at)
        ");

        // Create monthly partitions for 2024-2025
        $months = [];
        for ($year = 2024; $year <= 2025; $year++) {
            for ($month = 1; $month <= 12; $month++) {
                $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
                $nextMonth = $month == 12 ? 1 : $month + 1;
                $nextYear = $month == 12 ? $year + 1 : $year;
                $nextMonthStr = str_pad($nextMonth, 2, '0', STR_PAD_LEFT);

                $suffix = "{$year}_" . $monthStr;
                $from = "{$year}-{$monthStr}-01";
                $to = "{$nextYear}-{$nextMonthStr}-01";

                DB::statement("
                    CREATE TABLE auth_events_{$suffix} PARTITION OF auth_events
                    FOR VALUES FROM ('{$from}') TO ('{$to}')
                ");
            }
        }

        // Create indexes
        DB::statement('CREATE INDEX idx_auth_events_user ON auth_events(user_id, occurred_at)');
        DB::statement('CREATE INDEX idx_auth_events_type ON auth_events(event_type, occurred_at)');
        DB::statement('CREATE INDEX idx_auth_events_session ON auth_events(session_id)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS auth_events CASCADE');
    }
};
