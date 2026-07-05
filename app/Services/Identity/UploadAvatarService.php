<?php

declare(strict_types=1);

namespace App\Services\Identity;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadAvatarService
{
    private const DISK = 'public';

    private const DIRECTORY = 'avatars';

    public function execute(User $user, UploadedFile $file): User
    {
        $previousPath = $user->avatar_path;

        $path = $file->store(self::DIRECTORY, self::DISK);

        $user->update(['avatar_path' => $path]);

        $this->deletePrevious($previousPath);

        return $user;
    }

    private function deletePrevious(?string $path): void
    {
        if ($path !== null && Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }
}
