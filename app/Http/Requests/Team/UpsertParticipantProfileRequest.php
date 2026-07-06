<?php

declare(strict_types=1);

namespace App\Http\Requests\Team;

use App\Models\ParticipantProfile;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpsertParticipantProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $profile = $this->user()->participantProfile;

        if ($profile === null) {
            return $this->user()->can('create', ParticipantProfile::class);
        }

        return $this->user()->can('update', $profile);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'bio' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:30'],
            'institution' => ['nullable', 'string', 'max:255'],
        ];
    }
}
