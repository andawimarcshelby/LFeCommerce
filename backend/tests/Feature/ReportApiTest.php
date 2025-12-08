<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\ReportJob;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReportApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test report preview endpoint
     */
    public function test_report_preview_returns_paginated_results(): void
    {
        $response = $this->postJson('/api/reports/preview', [
            'report_type' => 'detail',
            'filters' => [
                'date_from' => '2024-01-01',
                'date_to' => '2024-12-31',
            ],
            'page' => 1,
            'per_page' => 100,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'pagination' => [
                'current_page',
                'per_page',
                'total',
                'last_page',
            ],
            'meta' => [
                'query_time_ms',
                'report_type',
            ],
        ]);
    }

    /**
     * Test report preview validation
     */
    public function test_report_preview_requires_date_range(): void
    {
        $response = $this->postJson('/api/reports/preview', [
            'report_type' => 'detail',
            'filters' => [],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['filters.date_from', 'filters.date_to']);
    }

    /**
     * Test export job creation
     */
    public function test_can_create_export_job(): void
    {
        $response = $this->postJson('/api/reports/exports', [
            'report_type' => 'summary',
            'format' => 'xlsx',
            'filters' => [
                'date_from' => '2024-01-01',
                'date_to' => '2024-03-31',
            ],
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'job_id',
            'status',
        ]);

        $this->assertDatabaseHas('report_jobs', [
            'report_type' => 'summary',
            'format' => 'xlsx',
            'status' => 'queued',
        ]);
    }

    /**
     * Test export job status retrieval
     */
    public function test_can_get_export_job_status(): void
    {
        // Create a job first
        $job = ReportJob::create([
            'user_id' => 1,
            'report_type' => 'detail',
            'format' => 'pdf',
            'filters' => ['date_from' => '2024-01-01', 'date_to' => '2024-12-31'],
            'status' => 'running',
            'progress_percent' => 45,
        ]);

        $response = $this->getJson("/api/reports/exports/{$job->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $job->id,
            'status' => 'running',
            'progress_percent' => 45,
        ]);
    }

    /**
     * Test concurrent export limit
     */
    public function test_enforces_concurrent_export_limit(): void
    {
        // Create 5 running jobs
        for ($i = 0; $i < 5; $i++) {
            ReportJob::create([
                'user_id' => 1,
                'report_type' => 'detail',
                'format' => 'xlsx',
                'filters' => ['date_from' => '2024-01-01', 'date_to' => '2024-12-31'],
                'status' => 'running',
            ]);
        }

        // Try to create another one
        $response = $this->postJson('/api/reports/exports', [
            'report_type' => 'summary',
            'format' => 'xlsx',
            'filters' => [
                'date_from' => '2024-01-01',
                'date_to' => '2024-03-31',
            ],
        ]);

        $response->assertStatus(429);
        $response->assertJsonFragment(['error']);
    }

    /**
     * Test report types
     */
    public function test_supports_all_report_types(): void
    {
        $reportTypes = ['detail', 'summary', 'top_n', 'per_student'];

        foreach ($reportTypes as $reportType) {
            $response = $this->postJson('/api/reports/preview', [
                'report_type' => $reportType,
                'filters' => [
                    'date_from' => '2024-01-01',
                    'date_to' => '2024-12-31',
                ],
            ]);

            $response->assertStatus(200);
        }
    }

    /**
     * Test invalid report type
     */
    public function test_rejects_invalid_report_type(): void
    {
        $response = $this->postJson('/api/reports/preview', [
            'report_type' => 'invalid_type',
            'filters' => [
                'date_from' => '2024-01-01',
                'date_to' => '2024-12-31',
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['report_type']);
    }
}
