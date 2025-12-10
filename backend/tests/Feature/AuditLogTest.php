<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    /** @test */
    public function middleware_logs_authenticated_requests()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/reports/exports')
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'reports.index',
            'response_status' => 200,
        ]);
    }

    /** @test */
    public function middleware_redacts_sensitive_data()
    {
        $user = User::factory()->create();

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertStatus(200);

        $auditLog = AuditLog::where('user_id', $user->id)->first();

        if ($auditLog) {
            $requestData = $auditLog->request_data;
            $this->assertArrayNotHasKey('password', $requestData);
        }
    }

    /** @test */
    public function admin_can_view_audit_logs()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Create some audit logs
        AuditLog::factory()->count(10)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/audit/logs')
            ->assertStatus(200);

        $this->assertArrayHasKey('data', $response->json());
        $this->assertArrayHasKey('meta', $response->json());
    }

    /** @test */
    public function non_admin_cannot_view_audit_logs()
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/audit/logs')
            ->assertStatus(403);
    }

    /** @test */
    public function audit_logs_can_be_filtered_by_date()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        AuditLog::factory()->create(['created_at' => now()->subDays(5)]);
        AuditLog::factory()->create(['created_at' => now()->subDay()]);
        AuditLog::factory()->create(['created_at' => now()]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/audit/logs?start_date=' . now()->subDays(2)->toDateString() . '&end_date=' . now()->toDateString())
            ->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /** @test */
    public function audit_logs_can_be_filtered_by_status_code()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        AuditLog::factory()->create(['response_status' => 200]);
        AuditLog::factory()->create(['response_status' => 404]);
        AuditLog::factory()->create(['response_status' => 500]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/audit/logs?status=404')
            ->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals(404, $data[0]['response_status']);
    }

    /** @test */
    public function audit_statistics_return_correct_counts()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Create logs for different users and statuses
        AuditLog::factory()->count(5)->create(['created_at' => now()]);
        AuditLog::factory()->count(3)->create([
            'created_at' => now()->subDays(2),
            'response_status' => 500
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/audit/stats')
            ->assertStatus(200);

        $stats = $response->json();

        $this->assertArrayHasKey('total_logs', $stats);
        $this->assertArrayHasKey('today_logs', $stats);
        $this->assertArrayHasKey('error_count', $stats);
        $this->assertEquals(8, $stats['total_logs']);
        $this->assertEquals(5, $stats['today_logs']);
    }

    /** @test */
    public function pagination_works_correctly()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        AuditLog::factory()->count(100)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/audit/logs?page=1')
            ->assertStatus(200);

        $meta = $response->json('meta');

        $this->assertEquals(1, $meta['current_page']);
        $this->assertCount(50, $response->json('data')); // Default 50 per page
    }
}
