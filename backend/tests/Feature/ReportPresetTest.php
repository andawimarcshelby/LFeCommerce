<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\ReportPreset;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReportPresetTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test creating a preset
     */
    public function test_can_create_preset(): void
    {
        $response = $this->postJson('/api/reports/presets', [
            'name' => 'My Weekly Report',
            'report_type' => 'detail',
            'filters' => [
                'date_from' => '2024-01-01',
                'date_to' => '2024-01-07',
                'event_types' => ['page_view', 'quiz_attempt'],
            ],
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'data' => ['id', 'name', 'report_type', 'filters'],
        ]);

        $this->assertDatabaseHas('report_presets', [
            'name' => 'My Weekly Report',
            'report_type' => 'detail',
        ]);
    }

    /**
     * Test duplicate preset name
     */
    public function test_cannot_create_duplicate_preset_name(): void
    {
        // Create first preset
        ReportPreset::create([
            'user_id' => 1,
            'name' => 'Existing Preset',
            'report_type' => 'summary',
            'filters' => [],
        ]);

        // Try to create duplicate
        $response = $this->postJson('/api/reports/presets', [
            'name' => 'Existing Preset',
            'report_type' => 'detail',
            'filters' => [],
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['error']);
    }

    /**
     * Test listing presets
     */
    public function test_can_list_presets(): void
    {
        // Create some presets
        ReportPreset::factory()->count(3)->create(['user_id' => 1]);

        $response = $this->getJson('/api/reports/presets');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'report_type', 'filters', 'created_at'],
            ],
        ]);
        $response->assertJsonCount(3, 'data');
    }

    /**
     * Test updating a preset
     */
    public function test_can_update_preset(): void
    {
        $preset = ReportPreset::create([
            'user_id' => 1,
            'name' => 'Original Name',
            'report_type' => 'detail',
            'filters' => [],
        ]);

        $response = $this->putJson("/api/reports/presets/{$preset->id}", [
            'name' => 'Updated Name',
            'filters' => ['date_from' => '2024-01-01'],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('report_presets', [
            'id' => $preset->id,
            'name' => 'Updated Name',
        ]);
    }

    /**
     * Test deleting a preset
     */
    public function test_can_delete_preset(): void
    {
        $preset = ReportPreset::create([
            'user_id' => 1,
            'name' => 'To Delete',
            'report_type' => 'summary',
            'filters' => [],
        ]);

        $response = $this->deleteJson("/api/reports/presets/{$preset->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('report_presets', [
            'id' => $preset->id,
        ]);
    }
}
