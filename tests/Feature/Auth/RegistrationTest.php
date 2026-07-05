<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register_with_organization(): void
    {
        $response = $this->post('/register', [
            'organization_name' => 'Acme Hackathons',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseHas('organizations', [
            'name' => 'Acme Hackathons',
            'slug' => 'acme-hackathons',
        ]);

        $organization = Organization::query()->where('slug', 'acme-hackathons')->first();

        $this->assertNotNull($organization);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'organization_id' => $organization->id,
            'role' => UserRole::Organizer->value,
        ]);
    }

    public function test_registration_requires_organization_name(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('organization_name');
        $this->assertGuest();
    }

    public function test_same_email_can_register_in_different_organizations(): void
    {
        Organization::factory()->create(['name' => 'First Org', 'slug' => 'first-org']);

        User::factory()->create([
            'email' => 'shared@example.com',
            'organization_id' => Organization::query()->where('slug', 'first-org')->value('id'),
        ]);

        $response = $this->post('/register', [
            'organization_name' => 'Second Org',
            'name' => 'Another User',
            'email' => 'shared@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();

        $this->assertSame(2, User::query()->where('email', 'shared@example.com')->count());
    }
}
