<?php

namespace App\Http\Requests\Api\V1\Finance;

use App\Http\Requests\Api\V1\ApiFormRequest;
use App\Models\JournalEntry;
use Illuminate\Validation\Rule;

class PostJournalRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', JournalEntry::class);
    }

    public function rules(): array
    {
        $schoolId = $this->user()?->school_id;

        return [
            'description' => ['required', 'string'],
            'entry_date' => ['nullable', 'date'],
            'currency' => ['nullable', 'string', 'size:3'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => [
                'required',
                'integer',
                Rule::exists('chart_of_accounts', 'id')->when(
                    $schoolId !== null,
                    fn ($rule) => $rule->where('school_id', $schoolId)
                ),
            ],
            'lines.*.debit' => ['nullable', 'numeric'],
            'lines.*.credit' => ['nullable', 'numeric'],
            'lines.*.currency' => ['nullable', 'string', 'size:3'],
            'lines.*.memo' => ['nullable', 'string'],
        ];
    }
}
