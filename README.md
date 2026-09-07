# Task Desk — Prueba técnica

Mini aplicación de gestión de usuarios y tareas con **Laravel 12, PHP 8.4, MySQL, Sanctum, Blade y JavaScript ES6**.

## Requisitos

- PHP **8.4 o superior** con Composer 2, extensiones PDO MySQL, mbstring, openssl, tokenizer, XML, ctype, fileinfo y curl. El lock se resolvió con PHP 8.4.
- MySQL 8.0+ (verificado con MySQL 8.4.7).
- Node.js 22+ solamente para las pruebas JavaScript. La interfaz funciona sin npm install ni compilación.
- Navegador moderno con módulos ES6 y `<dialog>`.

## Instalación

1. Clonar este repositorio y entrar a su carpeta.
2. Instalar dependencias y crear la configuración:

   ```sh
   composer install
   cp .env.example .env
   php artisan key:generate
   ```

   En PowerShell, usar `Copy-Item .env.example .env` en lugar de `cp`.

3. Crear una base vacía en MySQL:

   ```sql
   CREATE DATABASE nachmx CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

4. Ajustar `.env`:

   ```dotenv
   APP_NAME="Task Desk"
   APP_ENV=local
   APP_DEBUG=true
   APP_URL=http://127.0.0.1:8000
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=nachmx
   DB_USERNAME=root
   DB_PASSWORD=
   SESSION_DRIVER=file
   CACHE_STORE=file
   QUEUE_CONNECTION=sync
   ```

   Usar las credenciales reales de la instalación. La conexión MySQL especifica InnoDB para garantizar claves foráneas y transacciones, incluso si el servidor tiene MyISAM como motor predeterminado.

5. Crear tablas y datos de ejemplo:

   ```sh
   php artisan migrate --seed
   ```

   El seeder crea 3 usuarios y 8 tareas. Es idempotente: ejecutarlo otra vez no duplica esos registros ni sobrescribe su estado.

6. Emitir un token y arrancar:

   ```sh
   php artisan app:token ana@example.com
   php artisan serve
   ```

7. Abrir **http://127.0.0.1:8000**, pulsar **Ingresar** y pegar el token.

### WAMP / Windows

Si PHP no está en PATH, en PowerShell:

```powershell
$env:PATH = 'C:\wamp64\bin\php\php8.4.15;' + $env:PATH
php artisan serve
```

Para Apache, el DocumentRoot debe apuntar a `public/` y permitir el `.htaccess` incluido. No exponer la raíz del repositorio. Alternativamente, usar `php artisan serve`, que evita cambiar la configuración global de WAMP.

## Autenticación y alcance

- La API exige `Authorization: Bearer TOKEN` mediante `auth:sanctum`.
- Los tokens se generan **por consola**, únicamente para usuarios existentes, y vencen a las 24 horas. No se agregan contraseñas ni un endpoint público que emita tokens con solo conocer un correo.
- El esquema de `users` conserva los campos solicitados: id, name, email y timestamps. Sanctum añade su tabla `personal_access_tokens`.
- El token se conserva en **sessionStorage de esta pestaña**, para restaurar el acceso al recargar. No se comparte entre navegadores ni entre localhost y 127.0.0.1. En un navegador nuevo se pide el token al abrir; Sanctum vuelve a validarlo siempre. Si el almacenamiento está bloqueado, el acceso funciona solo en memoria.
- **Salir** revoca el token actual. Para emitir otro y revocar los anteriores: `php artisan app:token ana@example.com --revoke`.
- Es un **espacio compartido**: cualquier usuario con token puede listar/crear usuarios y gestionar las tareas de cualquiera. Esto permite la selección de usuarios solicitada. No hay roles ni aislamiento por organización en el enunciado.
- Producción: usar HTTPS, `APP_DEBUG=false` y credenciales MySQL específicas. No subir `.env`, tokens ni `vendor/`.

## Uso de la interfaz

Seleccionar una persona en el lateral para cargar sus tareas. El botón **+** junto a Equipo crea usuarios. **Nueva tarea** abre el formulario de título y descripción. La casilla completa una tarea; la acción es idempotente y no vuelve a marcarla pendiente. El botón **×** abre la confirmación de eliminación.

Los filtros Todas/Pendientes/Completadas y el orden por título (A–Z/Z–A) o fecha (recientes/antiguas) se aplican a los datos de la API con módulos JavaScript. Los contadores muestran el total del usuario, independientemente del filtro. Hay estados vacíos, indicadores de carga, reintento, mensajes de validación y bloqueo de envíos duplicados.

## API

Todos los endpoints están bajo `/api`, requieren token y tienen límite de 120 solicitudes/minuto por usuario.

| Método | Ruta | Resultado |
| --- | --- | --- |
| GET | /me | Usuario autenticado |
| DELETE | /token | Revocar token actual |
| GET | /users | Usuarios con conteos de tareas |
| POST | /users | Crear usuario: name, email |
| GET | /users/{user}/tasks | Tareas del usuario |
| POST | /users/{user}/tasks | Crear tarea: title, description |
| PATCH | /tasks/{task}/complete | Completar tarea; no requiere cuerpo |
| DELETE | /tasks/{task} | Eliminar tarea |

Ejemplos (reemplazar TOKEN y los identificadores):

```sh
curl http://127.0.0.1:8000/api/users -H "Accept: application/json" -H "Authorization: Bearer TOKEN"

curl -X POST http://127.0.0.1:8000/api/users/1/tasks -H "Authorization: Bearer TOKEN" -H "Accept: application/json" -H "Content-Type: application/json" -d '{"title":"Preparar entrega","description":"Verificar requisitos y pruebas."}'

curl -X PATCH http://127.0.0.1:8000/api/tasks/1/complete -H "Authorization: Bearer TOKEN" -H "Accept: application/json"
```

En PowerShell utilizar `curl.exe` (o un cliente REST) para evitar el alias de versiones antiguas; adaptar el escape JSON al shell.

**Respuestas:** listados y recursos en `{"data": ...}`; mutaciones incluyen `message`. Creación: **201**. Consulta/completar/eliminar: **200**. Errores: **401** token inválido/vencido, **404** recurso inexistente, **422** validaciones con `message` y `errors`, **429** límite de solicitudes, **500** mensaje genérico sin datos internos. Las excepciones siguen registrándose en el log de Laravel.

Validaciones: nombre y título obligatorios, hasta 255 caracteres; email válido, único y normalizado a minúsculas; descripción obligatoria, hasta 5000 caracteres. El cliente no puede forzar `user_id` ni `completed` al crear tareas.

## Arquitectura (8 líneas)

1. `routes/api.php` agrupa rutas REST con autenticación Sanctum y rate limiting.
2. Los controladores API reciben peticiones, delegan operaciones y devuelven JSON.
3. `StoreUserRequest` y `StoreTaskRequest` validan los datos antes de persistirlos.
4. `User` y `Task` usan Eloquent con relaciones hasMany/belongsTo y cast booleano.
5. `TaskManager` encapsula crear, completar, listar y eliminar tareas con tipos explícitos.
6. `IssueToken` emite tokens personales mediante consola con vencimiento y revocación.
7. Blade presenta la estructura; `api.js`, `tasks.js` y `app.js` separan HTTP, transformaciones y UI.
8. Migraciones, factories, seeder y pruebas mantienen el esquema y el comportamiento reproducibles.

## Pruebas

```sh
php artisan test
php vendor/bin/pint --test
npm test
```

Por defecto PHPUnit usa SQLite en memoria, sin tocar los datos locales. Para verificar también MySQL, **crear una base exclusiva de pruebas**:

```sql
CREATE DATABASE nachmx_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

PowerShell:

```powershell
$env:DB_CONNECTION = 'mysql'
$env:DB_DATABASE = 'nachmx_testing'
php artisan test
Remove-Item Env:DB_CONNECTION
Remove-Item Env:DB_DATABASE
```

Linux/macOS:

```sh
DB_CONNECTION=mysql DB_DATABASE=nachmx_testing php artisan test
```

Ajustar DB_USERNAME y DB_PASSWORD si difieren. **RefreshDatabase recrea las tablas en la base seleccionada: nunca ejecutar con la base de trabajo o producción.**

Cobertura: token real, token vencido, revocación, creación/listado de usuarios, duplicados, ciclo de tareas, separación por usuario, idempotencia al completar, límites y campos obligatorios, 404, 500 sin filtraciones, relaciones y borrado en cascada, seeder y comando de tokens. JavaScript comprueba filtros, cuatro órdenes, conteos, iniciales, cabeceras y manejo de errores de fetch. GitHub Actions repite las pruebas en SQLite y MySQL.

Consultar [docs/VERIFICACION.md](docs/VERIFICACION.md) para la evidencia y los límites de la revisión.

## Entrega en GitHub

El código está preparado con `.gitignore`, lock de Composer y workflow de pruebas. Crear un repositorio en la cuenta elegida y publicar este contenido (sin `.env`, `vendor`, logs ni tokens). Compartir acceso al evaluador indicado en la prueba es un paso externo a realizar por el propietario del repositorio. Esta implementación no envía invitaciones ni correos.
