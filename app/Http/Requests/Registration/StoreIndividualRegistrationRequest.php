<?php

declare(strict_types=1);

namespace App\Http\Requests\Registration;

use App\Models\Competition;
use App\Models\CompetitionCategory;
use App\Models\Registration;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIndividualRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $category = CompetitionCategory::query()->find($this->input('competition_category_id'));

        if ($category === null) {
            return false;
        }

        return $this->user()->can('createIndividual', [Registration::class, $category]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Competition $competition */
        $competition = $this->route('competition');

        return [
            'competition_category_id' => [
                'required',
                'integer',
                Rule::exists('competition_categories', 'id')->where('competition_id', $competition->id),
            ],
        ];
    }
}
