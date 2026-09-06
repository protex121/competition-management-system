<?php

declare(strict_types=1);

namespace App\Http\Requests\Submission;

use App\Models\Registration;
use App\Models\Submission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpsertSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Registration $registration */
        $registration = $this->route('registration');

        return $this->user()->can('manage', [Submission::class, $registration]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'project_url' => ['nullable', 'url', 'max:255'],
        ];
    }
}
