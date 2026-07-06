<?php

declare(strict_types=1);

namespace App\Http\Requests\Competition;

use App\Models\CompetitionCategory;
use Illuminate\Foundation\Http\FormRequest;

class DisableCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var CompetitionCategory $category */
        $category = $this->route('category');

        return $this->user()->can('disable', $category);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [];
    }
}
