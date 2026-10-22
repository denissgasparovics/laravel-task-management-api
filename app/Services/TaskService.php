<?php

namespace App\Services;

use App\Models\Task;
use Illuminate\Validation\ValidationException;
use App\Models\Project;
use App\Notifications\TaskAssignedNotification;

class TaskService
{
    public function update(Task $task, array $data): Task
    {
        $currentStatus = $task->status;
        $newStatus = $data['status'] ?? $currentStatus;

        if ($currentStatus === 'done' && $newStatus === 'todo') {
            throw ValidationException::withMessages([
                'status' => [
                    'A completed task cannot be moved back to todo.',
                ],
            ]);
        }

        $previousAssigneeId = $task->assignee_id;

        $task->update($data);
        $task->refresh();

        if (
            isset($data['assignee_id'])
            && $task->assignee_id !== null
            && (int) $task->assignee_id !== (int) $previousAssigneeId
        ) {
            $task->assignee->notify(
                new TaskAssignedNotification($task)
            );
        }

        return $task;
    }

    public function validateAssignee(Project $project, ?int $assigneeId): void
    {
        if ($assigneeId === null) {
            return;
        }

        $isOwner = $project->owner_id === $assigneeId;

        $isMember = $project->members()
            ->where('users.id', $assigneeId)
            ->exists();

        if (! $isOwner && ! $isMember) {
            throw ValidationException::withMessages([
                'assignee_id' => [
                    'The assignee must be the project owner or a project member.',
                ],
            ]);
        }
    }

    public function create(Project $project, array $data): Task
    {
        $task = $project->tasks()->create($data);

        if ($task->assignee_id !== null) {
            $task->load('project', 'assignee');

            $task->assignee->notify(
                new TaskAssignedNotification($task)
            );
        }

        return $task->load('assignee');
    }
}
