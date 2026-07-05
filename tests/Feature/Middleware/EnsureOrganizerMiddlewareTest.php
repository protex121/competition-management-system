<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EnsureOrganizerMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth', 'active', 'organizer'])
            ->get('/__test/organizer-only', fn () => response('ok'));
    }

    public function test_organizer_can_access_organizer_routes(): void
    {
        $organizer = User::factory()->organizer()->create();

        $this->actingAs($organizer)
            ->get('/__test/organizer-only')
            ->assertOk();
    }

    public function test_super_admin_can_access_organizer_routes(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get('/__test/organizer-only')
            ->assertOk();
    }

    public function test_participant_cannot_access_organizer_routes(): void
    {
        $participant = User::factory()->create();

        $this->actingAs($participant)
            ->get('/__test/organizer-only')
            ->assertForbidden();
    }
}
