<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_receive_a_sanctum_token(): void
    {
        $user = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Login successful')
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.role', 'admin')
            ->assertJsonStructure(['data' => ['user', 'token']]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ])->assertUnauthorized()
            ->assertJson(['success' => false, 'message' => 'Invalid credentials']);
    }

    public function test_login_validates_required_and_valid_input(): void
    {
        $this->postJson('/api/auth/login', [
            'email' => 'not-an-email',
        ])->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['email', 'password']]);
    }

    public function test_authenticated_user_can_view_me(): void
    {
        $user = User::factory()->create(['role' => 'technician']);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.role', 'technician');
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('logout-test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJson(['success' => true, 'message' => 'Logout successful']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        $user = User::factory()->create();
        $currentToken = $user->createToken('current')->plainTextToken;
        $otherToken = $user->createToken('other')->plainTextToken;

        $this->withToken($currentToken)->postJson('/api/auth/logout')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->assertNull(PersonalAccessToken::findToken($currentToken));
        $this->assertNotNull(PersonalAccessToken::findToken($otherToken));
    }

    public function test_role_middleware_allows_an_authorized_role(): void
    {
        Route::middleware(['auth:sanctum', 'role:admin,operator'])
            ->get('/api/test-role-access', fn () => response()->json(['success' => true]));

        $user = User::factory()->create(['role' => 'operator']);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/test-role-access')
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_role_middleware_rejects_an_unauthorized_role(): void
    {
        Route::middleware(['auth:sanctum', 'role:admin,operator'])
            ->get('/api/test-role-access', fn () => response()->json(['success' => true]));

        $user = User::factory()->create(['role' => 'technician']);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/test-role-access')
            ->assertForbidden()
            ->assertJson(['success' => false, 'message' => 'This action is unauthorized.']);
    }
}
