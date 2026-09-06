<?php

declare(strict_types=1);

namespace Tests\Feature\Submission;

use App\Enums\RegistrationMode;
use App\Enums\SubmissionStatus;
use App\Enums\TeamMemberRole;
use App\Enums\TeamMemberStatus;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\Submission;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Submission\Concerns\CreatesSubmissionFixtures;
use Tests\TestCase;

class SubmissionTest extends TestCase
{
    use CreatesSubmissionFixtures;
    use RefreshDatabase;

    public function test_participant_can_create_and_edit_draft_submission(): void
    {
        [$organization, , $category] = $this->createPublishedCompetitionWithActiveCategory();
        $participant = $this->createParticipantFor($organization);
        $registration = $this->createConfirmedRegistration($category, $participant);

        $this->actingAs($participant)
            ->put(route('registrations.submission.update', $registration), [
                'title' => 'My Hackathon Project',
                'description' => 'A description.',
            ])
            ->assertRedirect(route('registrations.submission.edit', $registration));

        $this->assertDatabaseHas('submissions', [
            'registration_id' => $registration->id,
            'title' => 'My Hackathon Project',
            'status' => SubmissionStatus::Draft->value,
        ]);

        $this->actingAs($participant)
            ->put(route('registrations.submission.update', $registration), [
                'title' => 'Renamed Project',
                'description' => 'Updated.',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('submissions', 1);
        $this->assertDatabaseHas('submissions', [
            'registration_id' => $registration->id,
            'title' => 'Renamed Project',
        ]);
    }

    public function test_upload_replaces_previous_file(): void
    {
        Storage::fake('local');

        [$organization, , $category] = $this->createPublishedCompetitionWithActiveCategory();
        $participant = $this->createParticipantFor($organization);
        $registration = $this->createConfirmedRegistration($category, $participant);
        $submission = Submission::factory()->create(['registration_id' => $registration->id]);

        $this->actingAs($participant)
            ->post(route('submissions.file.store', $submission), [
                'file' => UploadedFile::fake()->create('first.zip', 100),
            ])
            ->assertRedirect();

        $firstPath = Submission::withoutGlobalScopes()->findOrFail($submission->id)->file_path;
        Storage::disk('local')->assertExists($firstPath);

        $this->actingAs($participant)
            ->post(route('submissions.file.store', $submission), [
                'file' => UploadedFile::fake()->create('second.zip', 100),
            ])
            ->assertRedirect();

        Storage::disk('local')->assertMissing($firstPath);
        $this->assertDatabaseHas('submissions', [
            'id' => $submission->id,
            'file_original_name' => 'second.zip',
        ]);
    }

    public function test_finalize_happy_path(): void
    {
        [$organization, , $category] = $this->createPublishedCompetitionWithActiveCategory();
        $participant = $this->createParticipantFor($organization);
        $registration = $this->createConfirmedRegistration($category, $participant);
        $submission = Submission::factory()->create([
            'registration_id' => $registration->id,
            'description' => 'Enough content to finalize.',
        ]);

        $this->actingAs($participant)
            ->post(route('submissions.finalize', $submission))
            ->assertRedirect();

        $this->assertDatabaseHas('submissions', [
            'id' => $submission->id,
            'status' => SubmissionStatus::Finalized->value,
        ]);
        $this->assertNotNull(Submission::withoutGlobalScopes()->findOrFail($submission->id)->submitted_at);
    }

    public function test_finalize_blocked_after_deadline(): void
    {
        [$organization, , $category] = $this->createPublishedCompetitionWithActiveCategory([
            'submission_ends_at' => now()->subDay(),
        ]);
        $participant = $this->createParticipantFor($organization);
        $registration = $this->createConfirmedRegistration($category, $participant);
        $submission = Submission::factory()->create([
            'registration_id' => $registration->id,
            'description' => 'Some content.',
        ]);

        $this->actingAs($participant)
            ->post(route('submissions.finalize', $submission))
            ->assertSessionHasErrors('submission');

        $this->assertDatabaseHas('submissions', [
            'id' => $submission->id,
            'status' => SubmissionStatus::Draft->value,
        ]);
    }

    public function test_finalize_blocked_when_submission_is_empty(): void
    {
        [$organization, , $category] = $this->createPublishedCompetitionWithActiveCategory();
        $participant = $this->createParticipantFor($organization);
        $registration = $this->createConfirmedRegistration($category, $participant);
        $submission = Submission::factory()->create([
            'registration_id' => $registration->id,
            'description' => null,
            'project_url' => null,
            'file_path' => null,
        ]);

        $this->actingAs($participant)
            ->post(route('submissions.finalize', $submission))
            ->assertSessionHasErrors('submission');

        $this->assertDatabaseHas('submissions', [
            'id' => $submission->id,
            'status' => SubmissionStatus::Draft->value,
        ]);
    }

    public function test_edit_and_upload_blocked_once_finalized(): void
    {
        Storage::fake('local');

        [$organization, , $category] = $this->createPublishedCompetitionWithActiveCategory();
        $participant = $this->createParticipantFor($organization);
        $registration = $this->createConfirmedRegistration($category, $participant);
        $submission = Submission::factory()->finalized()->create([
            'registration_id' => $registration->id,
            'description' => 'Locked.',
        ]);

        $this->actingAs($participant)
            ->put(route('registrations.submission.update', $registration), [
                'title' => 'Trying to edit',
                'description' => 'Nope.',
            ])
            ->assertSessionHasErrors('submission');

        $this->actingAs($participant)
            ->post(route('submissions.file.store', $submission), [
                'file' => UploadedFile::fake()->create('late.zip', 10),
            ])
            ->assertForbidden();
    }

    public function test_active_team_member_who_is_not_captain_can_manage_team_submission(): void
    {
        [$organization, $competition, $category] = $this->createPublishedCompetitionWithActiveCategory([
            'registration_mode' => RegistrationMode::Team,
            'min_team_size' => 1,
        ]);
        $team = Team::factory()->approved()->create(['competition_id' => $competition->id]);
        $registration = Registration::factory()->create([
            'competition_category_id' => $category->id,
            'user_id' => null,
            'team_id' => $team->id,
        ]);
        $member = $this->createParticipantFor($organization);
        TeamMember::factory()->create([
            'team_id' => $team->id,
            'user_id' => $member->id,
            'role' => TeamMemberRole::Member,
            'status' => TeamMemberStatus::Active,
        ]);

        $this->actingAs($member)
            ->put(route('registrations.submission.update', $registration), [
                'title' => 'Team Project',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('submissions', [
            'registration_id' => $registration->id,
            'title' => 'Team Project',
        ]);
    }

    public function test_non_member_cannot_manage_registration_submission(): void
    {
        [$organization, , $category] = $this->createPublishedCompetitionWithActiveCategory();
        $participant = $this->createParticipantFor($organization);
        $registration = $this->createConfirmedRegistration($category, $participant);
        $other = $this->createParticipantFor($organization);

        $this->actingAs($other)
            ->put(route('registrations.submission.update', $registration), [
                'title' => 'Not mine',
            ])
            ->assertForbidden();
    }

    public function test_download_requires_view_ability(): void
    {
        Storage::fake('local');

        [$organization, , $category] = $this->createPublishedCompetitionWithActiveCategory();
        $participant = $this->createParticipantFor($organization);
        $registration = $this->createConfirmedRegistration($category, $participant);
        $submission = Submission::factory()->create(['registration_id' => $registration->id]);

        $this->actingAs($participant)
            ->post(route('submissions.file.store', $submission), [
                'file' => UploadedFile::fake()->create('doc.pdf', 50),
            ])
            ->assertRedirect();

        $this->actingAs($participant)
            ->get(route('submissions.file.download', $submission))
            ->assertOk();

        $unrelated = $this->createParticipantFor($organization);

        $this->actingAs($unrelated)
            ->get(route('submissions.file.download', $submission))
            ->assertForbidden();

        $outsider = $this->createParticipantFor(Organization::factory()->create());

        $this->actingAs($outsider)
            ->get(route('submissions.file.download', $submission))
            ->assertNotFound();
    }

    public function test_organizer_can_review_submissions_for_a_category(): void
    {
        [$organization, $competition, $category] = $this->createPublishedCompetitionWithActiveCategory();
        $participant = $this->createParticipantFor($organization);
        $registration = $this->createConfirmedRegistration($category, $participant);
        Submission::factory()->create(['registration_id' => $registration->id]);
        $organizer = User::factory()->organizer()->create(['organization_id' => $organization->id]);

        $this->actingAs($organizer)
            ->get(route('competitions.categories.submissions.index', [$competition, $category]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('submission/Review', shouldExist: false)
                ->has('submissions', 1));
    }
}
