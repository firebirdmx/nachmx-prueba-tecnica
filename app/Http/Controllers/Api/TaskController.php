<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskManager;
use Illuminate\Http\JsonResponse;

class TaskController extends Controller
{
    public function __construct(private readonly TaskManager $taskManager) {}

    public function index(User $user): JsonResponse
    {
        return response()->json(['data' => $this->taskManager->listUserTasks($user)]);
    }

    public function store(StoreTaskRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();

        return response()->json([
            'message' => 'Tarea creada.',
            'data' => $this->taskManager->createTask($user, $data['title'], $data['description']),
        ], 201);
    }

    public function complete(Task $task): JsonResponse
    {
        return response()->json([
            'message' => 'Tarea completada.',
            'data' => $this->taskManager->completeTask($task),
        ]);
    }

    public function destroy(Task $task): JsonResponse
    {
        $this->taskManager->deleteTask($task);

        return response()->json(['message' => 'Tarea eliminada.']);
    }
}
