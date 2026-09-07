<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class TaskManager
{
    public function createTask(User $user, string $title, string $description): Task
    {
        return $user->tasks()->create([
            'title' => $title,
            'description' => $description,
            'completed' => false,
        ]);
    }

    public function completeTask(Task $task): Task
    {
        $task->update(['completed' => true]);

        return $task->refresh();
    }

    /** @return Collection<int, Task> */
    public function listUserTasks(User $user): Collection
    {
        return $user->tasks()->latest()->orderByDesc('id')->get();
    }

    public function deleteTask(Task $task): void
    {
        $task->delete();
    }
}
