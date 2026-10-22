<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $this->user() !== null
            && $project instanceof Project
            && $this->user()->can('update', $project);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => [
                'sometimes',
                'required',
                'in:todo,in_progress,done',
            ],
            'priority' => [
                'sometimes',
                'required',
                'integer',
                'between:1,5',
            ],
            'due_date' => ['nullable', 'date'],
            'assignee_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
        ];
    }
}
