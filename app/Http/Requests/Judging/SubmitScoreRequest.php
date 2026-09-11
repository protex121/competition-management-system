<?php

declare(strict_types=1);

namespace App\Http\Requests\Judging;

use App\Models\Score;
use App\Models\Submission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SubmitScoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Submission $submission */
        $submission = $this->route('submission');

        return $this->user()->can('manage', [Score::class, $submission]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.rubric_criterion_id' => ['required', 'integer', 'exists:rubric_criteria,id'],
            'entries.*.score' => ['required', 'integer', 'min:0'],
            'entries.*.comment' => ['nullable', 'string'],
        ];
    }
}
