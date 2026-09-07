<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => User::query()->withCount([
            'tasks',
            'tasks as completed_tasks_count' => fn ($query) => $query->where('completed', true),
        ])->orderBy('name')->orderBy('id')->get()]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        return response()->json([
            'message' => 'Usuario creado.',
            'data' => User::create($request->validated()),
        ], 201);
    }
}
