<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $people = [
            ['Ana García', 'ana@example.com', [
                ['Definir el alcance del proyecto', 'Documentar los objetivos, entregables y criterios de aceptación con el equipo.', true],
                ['Diseñar el flujo de usuarios', 'Preparar el recorrido de creación de usuarios y asignación de tareas.', false],
                ['Revisar la documentación de la API', 'Verificar ejemplos de solicitudes, respuestas y códigos de error.', false],
                ['Preparar la presentación del avance', 'Reunir los resultados de la semana y los próximos pasos.', false],
            ]],
            ['Carlos Mendoza', 'carlos@example.com', [
                ['Configurar la base de datos', 'Crear las migraciones y comprobar la relación entre usuarios y tareas.', true],
                ['Implementar las validaciones', 'Validar campos obligatorios y mostrar mensajes claros en los formularios.', false],
            ]],
            ['Lucía Torres', 'lucia@example.com', [
                ['Revisar la experiencia móvil', 'Comprobar navegación, formularios y filtros en pantallas pequeñas.', false],
                ['Documentar la instalación', 'Escribir los pasos para configurar y ejecutar el proyecto desde cero.', true],
            ]],
        ];

        foreach ($people as [$name, $email, $tasks]) {
            $user = User::firstOrCreate(['email' => $email], ['name' => $name]);
            foreach ($tasks as [$title, $description, $completed]) {
                $user->tasks()->firstOrCreate(['title' => $title], compact('description', 'completed'));
            }
        }
    }
}
