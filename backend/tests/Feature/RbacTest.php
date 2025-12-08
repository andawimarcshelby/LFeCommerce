<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Run permissions seeder
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    }

    /** @test */
    public function student_can_view_reports()
    {
        $student = User::where('email', 'student@ppklms.test')->first();

        $this->assertTrue($student->hasRole('student'));
        $this->assertTrue($student->can('view reports'));
        $this->assertFalse($student->can('export reports'));
        $this->assertFalse($student->can('manage presets'));
    }

    /** @test */
    public function instructor_can_export_and_manage_presets()
    {
        $instructor = User::where('email', 'instructor@ppklms.test')->first();

        $this->assertTrue($instructor->hasRole('instructor'));
        $this->assertTrue($instructor->can('view reports'));
        $this->assertTrue($instructor->can('export reports'));
        $this->assertTrue($instructor->can('manage presets'));
        $this->assertTrue($instructor->can('manage scheduled reports'));
        $this->assertFalse($instructor->can('view all reports'));
    }

    /** @test */
    public function admin_has_all_permissions()
    {
        $admin = User::where('email', 'admin@ppklms.test')->first();

        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue($admin->can('view reports'));
        $this->assertTrue($admin->can('export reports'));
        $this->assertTrue($admin->can('manage presets'));
        $this->assertTrue($admin->can('view all reports'));
        $this->assertTrue($admin->can('manage scheduled reports'));
    }

    /** @test */
    public function student_cannot_access_export_endpoint()
    {
        $student = User::where('email', 'student@ppklms.test')->first();

        $response = $this->actingAs($student, 'sanctum')->postJson('/api/reports/exports', [
            'report_type' => 'detail',
            'format' => 'pdf',
            'filters' => [
                'date_from' => '2024-01-01',
                'date_to' => '2024-12-31',
            ],
        ]);

        $response->assertStatus(403); // Forbidden
    }

    /** @test */
    public function instructor_can_access_export_endpoint()
    {
        $instructor = User::where('email', 'instructor@ppklms.test')->first();

        // This will fail with validation/database errors but should not be 403
        $response = $this->actingAs($instructor, 'sanctum')->postJson('/api/reports/exports', [
            'report_type' => 'detail',
            'format' => 'pdf',
            'filters' => [
                'date_from' => '2024-01-01',
                'date_to' => '2024-12-31',
            ],
        ]);

        $response->assertStatus(201); // Created (or 422 if validation fails, but not 403)
    }

    /** @test */
    public function student_cannot_manage_presets()
    {
        $student = User::where('email', 'student@ppklms.test')->first();

        $response = $this->actingAs($student, 'sanctum')->postJson('/api/reports/presets', [
            'name' => 'My Preset',
            'report_type' => 'detail',
            'filters' => ['date_from' => '2024-01-01'],
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function instructor_can_manage_presets()
    {
        $instructor = User::where('email', 'instructor@ppklms.test')->first();

        $response = $this->actingAs($instructor, 'sanctum')->postJson('/api/reports/presets', [
            'name' => 'Instructor Preset',
            'report_type' => 'detail',
            'filters' => ['date_from' => '2024-01-01', 'date_to' => '2024-12-31'],
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'data' => ['id', 'name', 'report_type', 'filters'],
        ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_protected_routes()
    {
        // Preview requires POST
        $response = $this->postJson('/api/reports/preview', [
            'report_type' => 'detail',
            'filters' => [
                'date_from' => '2024-01-01',
                'date_to' => '2024-12-31',
            ],
        ]);
        $response->assertStatus(401); // Unauthorized

        $response = $this->getJson('/api/reports/exports');
        $response->assertStatus(401);

        $response = $this->getJson('/api/reports/presets');
        $response->assertStatus(401);
    }
}
