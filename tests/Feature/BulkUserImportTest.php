<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkUserImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create an admin user for authentication
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.com',
        ]);
    }

    public function test_bulk_import_with_valid_emails()
    {
        $emails = "user1@example.com\nuser2@example.com\nuser3@example.com";

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/users/bulk-import', [
                'emails' => $emails,
                'send_emails' => false,
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'results' => [
                'created' => [
                    '*' => ['email', 'name', 'password']
                ],
                'failed',
                'skipped',
            ]
        ]);

        $this->assertCount(3, $response->json('results.created'));
        $this->assertDatabaseCount('users', 4); // 3 new + 1 admin
    }

    public function test_bulk_import_with_comma_separated_emails()
    {
        $emails = "user1@example.com, user2@example.com, user3@example.com";

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/users/bulk-import', [
                'emails' => $emails,
                'send_emails' => false,
            ]);

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('results.created'));
    }

    public function test_bulk_import_skips_existing_emails()
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $emails = "existing@example.com\nuser1@example.com";

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/users/bulk-import', [
                'emails' => $emails,
                'send_emails' => false,
            ]);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('results.created'));
        $this->assertCount(1, $response->json('results.skipped'));
    }

    public function test_bulk_import_rejects_invalid_emails()
    {
        $emails = "invalid-email\nuser1@example.com\nnot-an-email";

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/users/bulk-import', [
                'emails' => $emails,
                'send_emails' => false,
            ]);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('results.created'));
        $this->assertCount(2, $response->json('results.failed'));
    }

    public function test_bulk_import_rejects_empty_input()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/users/bulk-import', [
                'emails' => '',
                'send_emails' => false,
            ]);

        $response->assertStatus(422);
        // Laravel validation returns errors in the response
        $response->assertJsonStructure(['errors']);
    }

    public function test_bulk_import_respects_max_limit()
    {
        $emails = implode("\n", array_map(fn($i) => "user{$i}@example.com", range(1, 501)));

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/users/bulk-import', [
                'emails' => $emails,
                'send_emails' => false,
            ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['success' => false]);
    }

    public function test_bulk_import_creates_users_with_user_role()
    {
        $emails = "user1@example.com\nuser2@example.com";

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/users/bulk-import', [
                'emails' => $emails,
                'send_emails' => false,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'email' => 'user1@example.com',
            'role' => 'user',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'user2@example.com',
            'role' => 'user',
        ]);
    }

    public function test_bulk_import_generates_unique_passwords()
    {
        $emails = "user1@example.com\nuser2@example.com\nuser3@example.com";

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/users/bulk-import', [
                'emails' => $emails,
                'send_emails' => false,
            ]);

        $response->assertStatus(200);

        $passwords = array_map(fn($user) => $user['password'], $response->json('results.created'));
        $uniquePasswords = array_unique($passwords);

        $this->assertCount(3, $uniquePasswords);
    }

    public function test_bulk_import_requires_admin_role()
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/admin/users/bulk-import', [
                'emails' => 'user1@example.com',
                'send_emails' => false,
            ]);

        $response->assertStatus(403);
    }

    public function test_bulk_import_requires_authentication()
    {
        $response = $this->postJson('/api/admin/users/bulk-import', [
            'emails' => 'user1@example.com',
            'send_emails' => false,
        ]);

        $response->assertStatus(401);
    }
}
