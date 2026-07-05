<?php

declare(strict_types=1);

namespace App\Http\Requests\Competition;

use App\Models\Competition;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompetitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Competition $competition */
        $competition = $this->route('competition');

        return $this->user()->can('update', $competition);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Competition $competition */
        $competition = $this->route('competition');

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'registration_starts_at' => ['nullable', 'date'],
            'registration_ends_at' => ['nullable', 'date', 'after_or_equal:registration_starts_at'],
            'max_participants' => ['nullable', 'integer', 'min:1'],
        ];

        if ($competition->isDraft()) {
            $rules['slug'] = [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('competitions', 'slug')
                    ->where('organization_id', $competition->organization_id)
                    ->whereNull('deleted_at')
                    ->ignore($competition->id),
            ];
        } else {
            $rules['slug'] = ['prohibited'];
        }

        return $rules;
    }
}
