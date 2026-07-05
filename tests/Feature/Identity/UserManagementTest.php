<?php

declare(strict_types=1);

namespace Tests\Feature\Identity;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    // --- Access control ---

    public function test_participant_cannot_access_user_management(): void
    {
        $participant = User::factory()->create(['role' => UserRole::Participant]);

        $this->actingAs($participant)
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_organizer_can_view_create_and_edit_pages(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $member = User::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($organizer)
            ->get(route('users.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('identity/users/Create')
                ->has('roles')
            );

        $this->actingAs($organizer)
            ->get(route('users.edit', $member))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('identity/users/Edit')
                ->where('user.id', $member->id)
                ->has('can.deactivate')
                ->has('can.reactivate')
                ->has('can.delete')
            );
    }

    // --- Listing ---

    public function test_organizer_can_list_users_in_their_organization(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $member = User::factory()->create(['organization_id' => $organization->id]);
        $outsider = User::factory()->create();

        $response = $this->actingAs($organizer)->get(route('users.index'));

        $response->assertOk()->assertInertia(fn ($page) => $page
            ->component('identity/users/Index')
            ->has('users.data', 2)
            ->where('users.data', fn ($users) => collect($users)->pluck('id')->contains($organizer->id)
                && collect($users)->pluck('id')->contains($member->id)
                && ! collect($users)->pluck('id')->contains($outsider->id))
        );
    }

    // --- Create ---

    public function test_organizer_can_create_a_user_in_their_organization(): void
    {
        $organizer = User::factory()->organizer()->create();

        $response = $this->actingAs($organizer)->post(route('users.store'), [
            'name' => 'New Participant',
            'email' => 'participant@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => UserRole::Participant->value,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'participant@example.com',
            'organization_id' => $organizer->organization_id,
            'role' => UserRole::Participant->value,
        ]);
    }

    public function test_organizer_cannot_assign_super_admin_role(): void
    {
        $organizer = User::factory()->organizer()->create();

        $response = $this->actingAs($organizer)->post(route('users.store'), [
            'name' => 'Bad Actor',
            'email' => 'bad@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => UserRole::SuperAdmin->value,
        ]);

        $response->assertSessionHasErrors('role');
    }

    public function test_super_admin_must_specify_organization_when_creating_users(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $organization = Organization::factory()->create();

        $this->actingAs($superAdmin)
            ->post(route('users.store'), [
                'name' => 'Cross Org User',
                'email' => 'cross@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => UserRole::Participant->value,
            ])
            ->assertSessionHasErrors('organization_id');

        $this->actingAs($superAdmin)
            ->post(route('users.store'), [
                'organization_id' => $organization->id,
                'name' => 'Cross Org User',
                'email' => 'cross@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => UserRole::Participant->value,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'cross@example.com',
            'organization_id' => $organization->id,
        ]);
    }

    // --- Update ---

    public function test_organizer_can_update_a_user_in_their_organization(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $member = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Participant,
        ]);

        $response = $this->actingAs($organizer)->put(route('users.update', $member), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'role' => UserRole::Judge->value,
        ]);

        $response->assertRedirect(route('users.edit', $member));

        $this->assertDatabaseHas('users', [
            'id' => $member->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'role' => UserRole::Judge->value,
        ]);
    }

    public function test_organizer_cannot_update_a_user_from_another_organization(): void
    {
        $organizer = User::factory()->organizer()->create();
        $outsider = User::factory()->create();

        $this->actingAs($organizer)
            ->put(route('users.update', $outsider), [
                'name' => 'Hacked',
                'email' => 'hacked@example.com',
                'role' => UserRole::Participant->value,
            ])
            ->assertForbidden();
    }

    // --- Deactivate / reactivate ---

    public function test_organizer_can_deactivate_a_user_in_their_organization(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $member = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Participant,
        ]);

        $this->actingAs($organizer)
            ->patch(route('users.deactivate', $member))
            ->assertRedirect(route('users.edit', $member));

        $member->refresh();

        $this->assertNotNull($member->deactivated_at);
    }

    public function test_organizer_can_reactivate_a_deactivated_user(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $member = User::factory()->deactivated()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Participant,
        ]);

        $this->actingAs($organizer)
            ->patch(route('users.reactivate', $member))
            ->assertRedirect(route('users.edit', $member));

        $member->refresh();

        $this->assertNull($member->deactivated_at);
    }

    public function test_organizer_cannot_deactivate_themselves(): void
    {
        $organizer = User::factory()->organizer()->create();

        $this->actingAs($organizer)
            ->patch(route('users.deactivate', $organizer))
            ->assertForbidden();
    }

    public function test_organizer_cannot_reactivate_an_active_user(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $member = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Participant,
        ]);

        $this->actingAs($organizer)
            ->patch(route('users.reactivate', $member))
            ->assertForbidden();
    }

    public function test_deactivated_user_cannot_access_the_application(): void
    {
        $user = User::factory()->deactivated()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    // --- Delete ---

    public function test_organizer_can_soft_delete_a_user_in_their_organization(): void
    {
        $organization = Organization::factory()->create();
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);
        $member = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::Participant,
        ]);

        $this->actingAs($organizer)
            ->delete(route('users.destroy', $member))
            ->assertRedirect(route('users.index'));

        $this->assertSoftDeleted($member);
    }
}
