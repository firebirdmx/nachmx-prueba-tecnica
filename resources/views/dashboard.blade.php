<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="api-base" content="{{ url('/api') }}">
    <meta name="theme-color" content="#f5f5f2">
    <title>Task Desk · Gestión de tareas</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 40 40'%3E%3Crect width='40' height='40' rx='12' fill='%23de562f'/%3E%3Cpath d='m11 20 6 6 13-14' fill='none' stroke='white' stroke-width='4'/%3E%3C/svg%3E">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script type="module" src="{{ asset('js/app.js') }}"></script>
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <a class="brand" href="{{ url('/') }}"><span class="brand-icon">✓</span> task<span>desk</span><small>TAREAS</small></a>
        <div class="workspace-label">MENÚ</div>
        <div class="nav-active"><span aria-hidden="true">▤</span> Tablero de tareas <span class="nav-dot"></span></div>
        <div class="team-heading"><span>EQUIPO <span id="user-count" class="count">0</span></span><button id="add-user" class="icon-button" aria-label="Crear usuario" title="Crear usuario" disabled>+</button></div>
        <label class="search-box"><span aria-hidden="true">⌕</span><input id="user-search" type="search" placeholder="Buscar persona…" aria-label="Buscar usuario" disabled></label>
        <div id="users" class="user-list" aria-label="Usuarios"><p class="sidebar-hint">Ingresa para ver los usuarios.</p></div>
        <div class="sidebar-bottom"><div class="workspace-note"><span class="small-mark">✓</span><div>Tareas del equipo<p>Consulta las tareas de cada usuario.</p></div></div><div class="connection"><span id="connection-dot"></span><span id="connection-label">Sin sesión</span><button id="logout" class="text-button" hidden>Salir</button></div></div>
    </aside>
    <main>
        <header class="topbar"><span>Inicio <span class="slash">/</span> <strong>Tablero de tareas</strong></span><span class="topbar-tag">Gestión de tareas <span aria-hidden="true">↗</span></span></header>
        <section class="content">
            <div class="page-heading"><div><div class="eyebrow">RESUMEN</div><h1>Tablero de tareas</h1><p>Selecciona un usuario para consultar y administrar sus tareas.</p></div><button id="new-task" class="button primary" disabled><span aria-hidden="true">+</span> Nueva tarea</button></div>
            <div class="stats" aria-label="Resumen del usuario seleccionado"><article><span class="stat-icon neutral">▤</span><div><p>Total de tareas</p><strong id="stat-total">—</strong></div><small>Tareas del usuario seleccionado</small></article><article><span class="stat-icon orange">◷</span><div><p>Pendientes</p><strong id="stat-pending">—</strong></div><small>Tareas por terminar</small></article><article><span class="stat-icon green">✓</span><div><p>Completadas</p><strong id="stat-done">—</strong></div><small>Tareas terminadas</small></article></div>
            <section class="task-panel" aria-labelledby="tasks-title">
                <div class="panel-heading"><div class="person-heading"><span id="selected-avatar" class="avatar large">—</span><div><h2 id="tasks-title">Tareas</h2><p id="selected-email">Selecciona un usuario para empezar.</p></div></div><span class="panel-label">POR USUARIO</span></div>
                <div class="toolbar"><div class="filters" role="group" aria-label="Filtrar tareas"><button data-filter="all" aria-pressed="true">Todas <span id="filter-all">0</span></button><button data-filter="pending" aria-pressed="false">Pendientes <span id="filter-pending">0</span></button><button data-filter="completed" aria-pressed="false">Completadas <span id="filter-completed">0</span></button></div><label class="sort-label">Ordenar por <select id="sort" aria-label="Ordenar tareas"><option value="date-desc">Más recientes</option><option value="date-asc">Más antiguas</option><option value="title-asc">Título: A–Z</option><option value="title-desc">Título: Z–A</option></select></label></div>
                <div id="task-error" class="panel-error" role="alert" hidden></div>
                <div id="tasks" class="tasks" aria-live="polite"><div class="empty-state"><span class="empty-icon">✓</span><h3>Ingresa para ver las tareas</h3><p>Usa tu token para cargar los usuarios y sus tareas.</p><button id="open-access" class="button primary">Ingresar <span aria-hidden="true">↗</span></button></div></div>
                <div class="panel-footer"><span id="task-count">Sin tareas cargadas</span><span><span class="mini-dot"></span> Listado de tareas</span></div>
            </section>
            <footer class="page-footer"><span>taskdesk <span class="footer-dot">·</span> Gestión de tareas</span><span>Gestión de equipo</span></footer>
        </section>
    </main>
</div>
<dialog id="access-dialog" aria-labelledby="access-title">
    <form id="access-form">
        <div class="dialog-heading"><span class="eyebrow">ACCESO</span><button type="button" class="icon-button" data-close aria-label="Cerrar">×</button></div>
        <h2 id="access-title">Ingresar</h2><p class="dialog-description">Pega tu token de acceso para continuar.</p>
        <label for="token">Token de acceso</label><input id="token" name="token" type="password" required autocomplete="off" placeholder="Pega tu token personal" spellcheck="false">
        <p class="field-hint">Puedes recargar esta pestaña sin volver a ingresar. El token dura 24 horas y deja de funcionar al cerrar sesión.</p>
        <p class="form-error" role="alert" hidden></p><div class="dialog-actions"><button class="button primary" type="submit">Ingresar <span aria-hidden="true">→</span></button></div>
    </form>
</dialog>
<dialog id="task-dialog" aria-labelledby="task-dialog-title">
    <form id="task-form">
        <div class="dialog-heading"><span class="eyebrow">TAREAS</span><button type="button" class="icon-button" data-close aria-label="Cerrar">×</button></div>
        <h2 id="task-dialog-title">Nueva tarea</h2><p class="dialog-description">Asignada a <strong id="task-assignee"></strong>.</p>
        <label for="task-title">Título <span>*</span></label><input id="task-title" name="title" required maxlength="255" placeholder="Título de la tarea">
        <label for="task-description">Descripción <span>*</span></label><textarea id="task-description" name="description" rows="4" required maxlength="5000" placeholder="Describe lo que hay que hacer."></textarea>
        <p class="form-error" role="alert" hidden></p><div class="dialog-actions"><button type="button" class="button secondary" data-close>Cancelar</button><button type="submit" class="button primary">Crear tarea <span aria-hidden="true">↗</span></button></div>
    </form>
</dialog>
<dialog id="user-dialog" aria-labelledby="user-dialog-title">
    <form id="user-form">
        <div class="dialog-heading"><span class="eyebrow">USUARIOS</span><button type="button" class="icon-button" data-close aria-label="Cerrar">×</button></div>
        <h2 id="user-dialog-title">Nuevo usuario</h2><p class="dialog-description">Ingresa el nombre y correo del usuario.</p>
        <label for="user-name">Nombre <span>*</span></label><input id="user-name" name="name" required maxlength="255" autocomplete="name" placeholder="Nombre completo">
        <label for="user-email">Correo electrónico <span>*</span></label><input id="user-email" name="email" type="email" required maxlength="255" autocomplete="email" placeholder="nombre@ejemplo.com">
        <p class="form-error" role="alert" hidden></p><div class="dialog-actions"><button type="button" class="button secondary" data-close>Cancelar</button><button type="submit" class="button primary">Crear usuario</button></div>
    </form>
</dialog>
<dialog id="delete-dialog" aria-labelledby="delete-title"><form id="delete-form"><div class="dialog-heading"><span class="eyebrow">ELIMINAR TAREA</span><button type="button" class="icon-button" data-close aria-label="Cerrar">×</button></div><h2 id="delete-title">¿Eliminar esta tarea?</h2><p id="delete-description" class="dialog-description"></p><p class="field-hint">Esta acción no se puede deshacer.</p><p class="form-error" role="alert" hidden></p><div class="dialog-actions"><button type="button" class="button secondary" data-close>Cancelar</button><button type="submit" class="button danger">Eliminar tarea</button></div></form></dialog>
<div id="toast" class="toast" role="status" hidden></div>
<noscript>Activa JavaScript para utilizar el tablero de tareas.</noscript>
</body>
</html>
