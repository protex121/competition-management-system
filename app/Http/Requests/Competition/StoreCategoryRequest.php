<?php

declare(strict_types=1);

namespace App\Http\Requests\Competition;

use App\Models\Competition;
use App\Models\CompetitionCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Competition $competition */
        $competition = $this->route('competition');

        return $this->user()->can('create', [CompetitionCategory::class, $competition]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Competition $competition */
        $competition = $this->route('competition');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('competition_categories', 'slug')
                    ->where('competition_id', $competition->id)
                    ->whereNull('deleted_at'),
            ],
            'description' => ['nullable', 'string'],
            'max_participants' => ['nullable', 'integer', 'min:1'],
            'registration_ends_at' => ['nullable', 'date'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
