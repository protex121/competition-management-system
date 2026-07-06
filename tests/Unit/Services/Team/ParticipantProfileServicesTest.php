<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Team;

use App\Models\ParticipantProfile;
use App\Models\User;
use App\Services\Team\ShowParticipantProfileService;
use App\Services\Team\UpsertParticipantProfileService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParticipantProfileServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_upsert_creates_and_updates_profile(): void
    {
        $participant = User::factory()->create();
        $service = new UpsertParticipantProfileService;

        $created = $service->execute($participant, [
            'bio' => 'Hello',
            'institution' => 'Acme University',
        ]);

        $this->assertSame('Hello', $created->bio);
        $this->assertDatabaseHas('participant_profiles', [
            'user_id' => $participant->id,
            'institution' => 'Acme University',
        ]);

        $updated = $service->execute($participant, [
            'bio' => 'Updated bio',
            'phone' => '+62001',
        ]);

        $this->assertSame($created->id, $updated->id);
        $this->assertSame('Updated bio', $updated->bio);
        $this->assertSame('+62001', $updated->phone);
    }

    public function test_show_allows_participant_to_view_own_profile(): void
    {
        $participant = User::factory()->create();
        $profile = ParticipantProfile::factory()->create(['user_id' => $participant->id]);
        $service = new ShowParticipantProfileService;

        $result = $service->execute($participant, $participant);

        $this->assertTrue($profile->is($result));
    }

    public function test_show_denies_cross_org_organizer(): void
    {
        $profile = ParticipantProfile::factory()->create();
        $subject = User::query()->findOrFail($profile->user_id);
        $organizer = User::factory()->organizer()->create();
        $service = new ShowParticipantProfileService;

        $this->expectException(AuthorizationException::class);

        $service->execute($organizer, $subject);
    }
}
