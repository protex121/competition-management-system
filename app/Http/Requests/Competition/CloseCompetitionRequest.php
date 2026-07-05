<?php

declare(strict_types=1);

namespace App\Http\Requests\Competition;

use App\Models\Competition;
use Illuminate\Foundation\Http\FormRequest;

class CloseCompetitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Competition $competition */
        $competition = $this->route('competition');

        return $this->user()->can('close', $competition);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [];
    }
}
