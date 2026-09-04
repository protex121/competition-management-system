<?php

declare(strict_types=1);

namespace App\Http\Requests\Registration;

use App\Models\CompetitionCategory;
use App\Models\Registration;
use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeamRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Team $team */
        $team = $this->route('team');
        $category = CompetitionCategory::query()->find($this->input('competition_category_id'));

        if ($category === null) {
            return false;
        }

        return $this->user()->can('createForTeam', [Registration::class, $team, $category]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Team $team */
        $team = $this->route('team');

        return [
            'competition_category_id' => [
                'required',
                'integer',
                Rule::exists('competition_categories', 'id')->where('competition_id', $team->competition_id),
            ],
        ];
    }
}
