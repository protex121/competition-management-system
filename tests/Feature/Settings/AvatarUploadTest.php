<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AvatarUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_an_avatar(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post('/settings/profile/avatar', [
                'avatar' => UploadedFile::fake()->image('avatar.jpg', 300, 300),
            ]);

        $response->assertSessionHasNoErrors()->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertNotNull($user->avatar_path);
        Storage::disk('public')->assertExists($user->avatar_path);
    }

    public function test_uploading_a_new_avatar_deletes_the_previous_one(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)->post('/settings/profile/avatar', [
            'avatar' => UploadedFile::fake()->image('first.jpg', 300, 300),
        ]);

        $firstPath = $user->refresh()->avatar_path;

        $this->actingAs($user)->post('/settings/profile/avatar', [
            'avatar' => UploadedFile::fake()->image('second.jpg', 300, 300),
        ]);

        $secondPath = $user->refresh()->avatar_path;

        $this->assertNotSame($firstPath, $secondPath);
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($secondPath);
    }

    public function test_non_image_files_are_rejected(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post('/settings/profile/avatar', [
                'avatar' => UploadedFile::fake()->create('malware.pdf', 100, 'application/pdf'),
            ]);

        $response->assertSessionHasErrors('avatar');
        $this->assertNull($user->refresh()->avatar_path);
    }

    public function test_oversized_images_are_rejected(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post('/settings/profile/avatar', [
                'avatar' => UploadedFile::fake()->image('huge.jpg', 300, 300)->size(3000),
            ]);

        $response->assertSessionHasErrors('avatar');
    }
}
