<?php

declare(strict_types=1);

namespace App\Http\Requests\Competition;

use App\Enums\RegistrationMode;
use App\Http\Requests\Competition\Concerns\ValidatesRegistrationSettings;
use App\Models\Competition;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompetitionRequest extends FormRequest
{
    use ValidatesRegistrationSettings;

    public function authorize(): bool
    {
        return $this->user()->can('create', Competition::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $organizationId = $this->user()->isSuperAdmin()
            ? $this->integer('organization_id')
            : $this->user()->organization_id;

        return [
            'organization_id' => [
                Rule::requiredIf($this->user()->isSuperAdmin()),
                'nullable',
                'integer',
                'exists:organizations,id',
            ],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('competitions', 'slug')
                    ->where('organization_id', $organizationId)
                    ->whereNull('deleted_at'),
            ],
            'description' => ['nullable', 'string'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'registration_starts_at' => ['nullable', 'date'],
            'registration_ends_at' => ['nullable', 'date', 'after_or_equal:registration_starts_at'],
            'max_participants' => ['nullable', 'integer', 'min:1'],
            ...$this->registrationSettingsRules(),
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('registration_mode')) {
            $this->merge([
                'registration_mode' => RegistrationMode::Individual->value,
            ]);
        }

        $this->merge([
            'requires_coach' => $this->boolean('requires_coach'),
        ]);
    }
}
