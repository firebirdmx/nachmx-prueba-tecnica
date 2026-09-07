<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use App\Services\TaskManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_requires_a_valid_personal_token(): void
    {
        $this->getJson('/api/users')->assertUnauthorized();
        $this->withToken('invalid')->getJson('/api/users')->assertUnauthorized();
        $user = User::factory()->create();
        $token = $user->createToken('test', ['*'], now()->addHour());
        $this->withToken($token->plainTextToken)->getJson('/api/me')
            ->assertOk()->assertJsonPath('data.id', $user->id);
    }

    public function test_expired_token_is_rejected(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('expired', ['*'], now()->subMinute());
        $this->withToken($token->plainTextToken)->getJson('/api/users')->assertUnauthorized();
    }

    public function test_logout_revokes_the_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test');
        $this->withToken($token->plainTextToken)->deleteJson('/api/token')->assertOk();
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
        $this->app['auth']->forgetGuards();
        $this->withToken($token->plainTextToken)->getJson('/api/users')->assertUnauthorized();
    }

    public function test_users_can_be_created_and_listed_with_counts(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/users', ['name' => 'Ana', 'email' => ' ANA@EXAMPLE.COM '])
            ->assertCreated()->assertJsonPath('data.email', 'ana@example.com');
        $this->getJson('/api/users')->assertOk()->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [['id', 'name', 'email', 'tasks_count', 'completed_tasks_count']]]);
    }

    public function test_invalid_and_duplicate_users_return_422(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->postJson('/api/users', [])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email']);
        $this->postJson('/api/users', ['name' => 'Ana', 'email' => 'bad'])->assertUnprocessable();
        $this->postJson('/api/users', ['name' => 'Ana', 'email' => $user->email])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_task_lifecycle_and_scoped_user_listing(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $owner = User::factory()->create();
        $other = Task::factory()->create();
        $response = $this->postJson("/api/users/{$owner->id}/tasks", [
            'title' => 'Preparar entrega', 'description' => 'Revisar todo.',
            'completed' => true, 'user_id' => $other->user_id,
        ])->assertCreated()->assertJsonPath('data.completed', false)->assertJsonPath('data.user_id', $owner->id);
        $id = $response->json('data.id');
        $this->getJson("/api/users/{$owner->id}/tasks")->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $id);
        $this->patchJson("/api/tasks/{$id}/complete")->assertOk()->assertJsonPath('data.completed', true);
        $this->patchJson("/api/tasks/{$id}/complete")->assertOk()->assertJsonPath('data.completed', true);
        $this->deleteJson("/api/tasks/{$id}")->assertOk();
        $this->assertDatabaseMissing('tasks', ['id' => $id]);
        $this->assertDatabaseHas('tasks', ['id' => $other->id]);
    }

    public function test_task_validation_limits_and_missing_resources(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->postJson("/api/users/{$user->id}/tasks", [])
            ->assertUnprocessable()->assertJsonValidationErrors(['title', 'description']);
        $this->postJson("/api/users/{$user->id}/tasks", ['title' => str_repeat('a', 256), 'description' => str_repeat('x', 5001)])
            ->assertUnprocessable()->assertJsonValidationErrors(['title', 'description']);
        $this->postJson("/api/users/{$user->id}/tasks", ['title' => '   ', 'description' => '  '])->assertUnprocessable();
        $this->getJson('/api/users/999999/tasks')->assertNotFound()->assertExactJson(['message' => 'No se encontró el recurso solicitado.']);
        $this->postJson('/api/users/999999/tasks', ['title' => 'X', 'description' => 'Y'])->assertNotFound();
        $this->patchJson('/api/tasks/999999/complete')->assertNotFound();
        $this->deleteJson('/api/tasks/999999')->assertNotFound();
    }

    public function test_server_error_has_safe_json_even_when_debug_is_enabled(): void
    {
        config(['app.debug' => true]);
        Route::get('/api/test-failure', fn () => throw new \RuntimeException('secret database detail'));
        $this->getJson('/api/test-failure')->assertStatus(500)
            ->assertExactJson(['message' => 'No fue posible procesar la solicitud. Intenta de nuevo.']);
    }

    public function test_task_manager_relationship_and_cascade_delete(): void
    {
        $manager = app(TaskManager::class);
        $user = User::factory()->create();
        $task = $manager->createTask($user, 'OOP', 'Servicio tipado');
        $this->assertTrue($task->user->is($user));
        $this->assertFalse($task->completed);
        $this->assertTrue($manager->completeTask($task)->completed);
        $this->assertCount(1, $manager->listUserTasks($user));
        $user->delete();
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_token_command_and_idempotent_seeder(): void
    {
        $this->seed();
        $this->seed();
        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseCount('tasks', 8);
        $this->artisan('app:token', ['email' => 'ana@example.com'])->assertSuccessful();
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->artisan('app:token', ['email' => 'ana@example.com', '--revoke' => true])->assertSuccessful();
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->artisan('app:token', ['email' => 'missing@example.com'])->assertFailed();
    }
}
