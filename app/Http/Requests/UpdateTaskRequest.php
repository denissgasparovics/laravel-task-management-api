<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');
        $task = $this->route('task');

        return $this->user() !== null
            && $project instanceof Project
            && $task instanceof Task
            && $task->project_id === $project->id
            && $this->user()->can('update', $project);
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
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
            'due_date' => ['sometimes', 'nullable', 'date'],
            'assignee_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:users,id',
            ],
        ];
    }
}
