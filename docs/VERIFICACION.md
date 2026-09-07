# Verificación de la entrega

Fecha: 7 de septiembre de 2026.

## Comprobaciones realizadas

- PHP 8.4.15; Laravel 12.69.1; Sanctum 4.3.3.
- Migraciones y seeder ejecutados en MySQL 8.4.7 con InnoDB.
- PHPUnit: 12 pruebas, 70 aserciones, aprobadas en SQLite en memoria y en MySQL (`nachmx_testing`).
- JavaScript: 5 pruebas aprobadas con el runner nativo de Node.
- Laravel Pint: formato aprobado.
- Navegador integrado: acceso con token real; listado y selección de usuarios; creación y finalización de una tarea; actualización de contadores; filtro de completadas; orden por título; mensaje de correo duplicado.
- Revisión visual en escritorio y viewport móvil solicitado de 390 × 844; sin desbordamiento horizontal del documento. El listado de usuarios tiene desplazamiento horizontal intencional en móvil.
- La eliminación y revocación están cubiertas por pruebas HTTP automatizadas. El formulario de confirmación de eliminación está implementado.
- Composer no reportó vulnerabilidades conocidas al instalar/resolver las dependencias.

## Límites y decisiones

- No se ejecutó una matriz de navegadores físicos, lectores de pantalla ni pruebas de carga.
- Los filtros y el ordenamiento se aplican en el frontend al listado completo por usuario, adecuado al alcance de mini aplicación. No se implementó paginación.
- Es un espacio de equipo compartido; no hay roles ni aislamiento entre organizaciones.
- La prueba manual deja una tarea de ejemplo adicional, “Verificación funcional del tablero”, completada para Ana García.
- No se publicó en GitHub ni se invitó al destinatario del documento. El workflow de GitHub Actions está incluido, pero su ejecución remota requiere publicar el repositorio.
- Actualización de acceso: el token se conserva en sessionStorage de la pestaña. La página pide el token automáticamente si no hay acceso guardado; al recargar restaura y valida el acceso.

## Reproducir

Ver los comandos en el README. Las pruebas MySQL verifican que el nombre de la base termine en `_testing` antes de permitir que RefreshDatabase actúe. La configuración predeterminada de pruebas usa SQLite en memoria.

## Corrección de acceso en otros navegadores

Se verificó en el navegador integrado que una página sin token abre el formulario de acceso automáticamente. Tras conectar un token válido, una recarga recupera los tres usuarios y sus 29 tareas sin solicitarlo otra vez. sessionStorage es independiente por pestaña y origen; en otro navegador debe ingresarse el token una vez. No se deshabilitó la autenticación de la API.