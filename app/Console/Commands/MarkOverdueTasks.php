<?php

namespace App\Console\Commands;

use App\Models\Task;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('tasks:mark-overdue')]
#[Description('Find tasks that are overdue')]
class MarkOverdueTasks extends Command
{
    public function handle(): int
    {
        $count = Task::query()
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', today())
            ->where('status', '!=', 'done')
            ->count();

        $this->info("Found {$count} overdue tasks.");

        return self::SUCCESS;
    }
}
