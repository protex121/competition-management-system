<?php

declare(strict_types=1);

namespace App\Http\Requests\Team;

use App\Enums\TeamMemberStatus;
use App\Models\Team;
use App\Models\TeamMember;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferCaptainRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Team $team */
        $team = $this->route('team');

        return $this->user()->can('manageMembers', $team);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Team $team */
        $team = $this->route('team');

        return [
            'member_id' => [
                'required',
                'integer',
                Rule::exists('team_members', 'id')
                    ->where('team_id', $team->id)
                    ->where('status', TeamMemberStatus::Active->value),
            ],
        ];
    }

    public function member(): TeamMember
    {
        /** @var TeamMember $member */
        $member = TeamMember::query()->findOrFail($this->integer('member_id'));

        return $member;
    }
}
