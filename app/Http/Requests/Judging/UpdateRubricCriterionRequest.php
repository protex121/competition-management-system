<?php

declare(strict_types=1);

namespace App\Http\Requests\Judging;

use App\Models\RubricCriterion;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRubricCriterionRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var RubricCriterion $criterion */
        $criterion = $this->route('criterion');

        return $this->user()->can('update', $criterion);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'max_score' => ['required', 'integer', 'min:1', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
