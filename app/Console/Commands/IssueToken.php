<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class IssueToken extends Command
{
    protected $signature = 'app:token {email : Correo de un usuario existente} {--revoke : Revocar sus tokens previos}';

    protected $description = 'Emite un token personal de Sanctum válido por 24 horas';

    public function handle(): int
    {
        $user = User::where('email', mb_strtolower(trim($this->argument('email'))))->first();
        if (! $user) {
            $this->error('Usuario no encontrado. Ejecuta primero las migraciones y el seeder.');

            return self::FAILURE;
        }
        if ($this->option('revoke')) {
            $user->tokens()->delete();
        }
        $this->line($user->createToken('task-desk', ['*'], now()->addDay())->plainTextToken);

        return self::SUCCESS;
    }
}
