<?php

declare(strict_types=1);

namespace App\Notifications\Registration;

use App\Models\Registration;
use Illuminate\Notifications\Notification;

class RegistrationConfirmed extends Notification
{
    public function __construct(
        private readonly Registration $registration,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'registration_id' => $this->registration->id,
            'type' => $this->registration->isTeam() ? 'team' : 'individual',
            'competition_id' => $this->registration->category->competition->id,
            'competition_name' => $this->registration->category->competition->name,
            'category_id' => $this->registration->category->id,
            'category_name' => $this->registration->category->name,
        ];
    }
}
