import { ApiClient } from './api.js';
import { readAccess, rememberAccess, forgetAccess } from './access.js';
import { initials, selectTasks, summarizeTasks } from './tasks.js';

const $ = selector => document.querySelector(selector);
const api = new ApiClient($('meta[name="api-base"]').content);
const state = { users: [], tasks: [], selectedId: null, filter: 'all', sort: 'date-desc', loaded: false, loadError: null };
let taskRequest;
let deleteId;
let toastTimer;
let generation = 0;

function element(tag, className, text) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== undefined) node.textContent = text;
    return node;
}

function notify(message) {
    clearTimeout(toastTimer);
    $('#toast').textContent = message;
    $('#toast').hidden = false;
    toastTimer = setTimeout(() => { $('#toast').hidden = true; }, 4500);
}

function formError(form, error) {
    const output = form.querySelector('.form-error');
    output.textContent = Object.values(error.errors || {}).flat().join('\n') || error.message;
    output.hidden = false;
}

function openDialog(id) {
    const dialog = $(id);
    dialog.querySelector('form').reset();
    dialog.querySelector('.form-error').hidden = true;
    dialog.showModal();
}

function resetAccess() {
    generation++;
    forgetAccess();
    taskRequest?.abort();
    api.token = '';
    Object.assign(state, { users: [], tasks: [], selectedId: null, loaded: false, loadError: null });
    $('#add-user').disabled = true;
    $('#user-search').disabled = true;
    $('#user-search').value = '';
    $('#logout').hidden = true;
    $('#connection-dot').classList.remove('online');
    $('#connection-label').textContent = 'Sin sesión';
    $('#task-error').hidden = true;
    document.querySelectorAll('dialog[open]').forEach(dialog => dialog.close());
    renderUsers();
    renderTasks();
}

function handleError(error) {
    if (error.status === 401) {
        resetAccess();
        openDialog('#access-dialog');
        formError($('#access-form'), error);
        return true;
    }
    return false;
}

function renderUsers() {
    $('#user-count').textContent = state.users.length;
    const query = $('#user-search').value.trim().toLocaleLowerCase('es');
    const users = state.users.filter(user => `${user.name} ${user.email}`.toLocaleLowerCase('es').includes(query));
    $('#users').replaceChildren();
    for (const user of users) {
        const button = element('button', 'user-button');
        button.setAttribute('aria-pressed', String(user.id === state.selectedId));
        button.setAttribute('aria-label', `Ver tareas de ${user.name}`);
        const copy = element('span', 'user-copy');
        copy.append(element('strong', '', user.name), element('small', '', `${user.tasks_count} tareas asignadas`));
        button.append(element('span', 'avatar', initials(user.name)), copy, element('span', 'user-number', user.tasks_count));
        button.addEventListener('click', () => selectUser(user.id));
        $('#users').append(button);
    }
    if (!users.length) $('#users').append(element('p', 'sidebar-hint', api.token ? 'No hay usuarios que mostrar.' : 'Ingresa para ver los usuarios.'));
}

function emptyState(title, description, action) {
    const container = element('div', 'empty-state');
    container.append(element('span', 'empty-icon', '✓'), element('h3', '', title), element('p', '', description));
    if (action) {
        const button = element('button', 'button primary', action.label);
        button.addEventListener('click', action.run);
        container.append(button);
    }
    return container;
}

function renderTasks() {
    const user = state.users.find(user => user.id === state.selectedId);
    $('#tasks-title').textContent = user ? `Tareas de ${user.name}` : 'Tareas';
    $('#selected-email').textContent = user?.email || 'Selecciona un usuario para empezar.';
    $('#selected-avatar').textContent = user ? initials(user.name) : '—';
    $('#new-task').disabled = !user || !state.loaded;
    const summary = summarizeTasks(state.tasks);
    for (const [key, value] of Object.entries({ total: summary.total, pending: summary.pending, done: summary.completed })) {
        $(`#stat-${key}`).textContent = state.loaded ? value : '—';
    }
    $('#filter-all').textContent = summary.total;
    $('#filter-pending').textContent = summary.pending;
    $('#filter-completed').textContent = summary.completed;
    document.querySelectorAll('[data-filter]').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.filter === state.filter)));
    const container = $('#tasks');
    container.replaceChildren();
    $('#task-count').textContent = 'Sin tareas cargadas';
    if (!api.token) {
        container.append(emptyState('Ingresa para ver las tareas', 'Usa tu token para cargar los usuarios y sus tareas.', { label: 'Ingresar ↗', run: () => openDialog('#access-dialog') }));
        return;
    }
    if (!user) {
        container.append(emptyState('No hay usuarios', 'Crea el primer usuario para asignarle tareas.', { label: '+ Crear usuario', run: () => openDialog('#user-dialog') }));
        return;
    }
    if (!state.loaded) {
        if (state.loadError) {
            container.append(emptyState('No pudimos cargar las tareas', 'Intenta cargar las tareas de nuevo.', { label: 'Reintentar', run: () => selectUser(state.selectedId) }));
            return;
        }
        container.append(element('div', 'loading', 'Cargando tareas…'));
        return;
    }
    const tasks = selectTasks(state.tasks, state.filter, state.sort);
    $('#task-count').textContent = `${tasks.length} de ${summary.total} tareas`;
    if (!tasks.length) {
        container.append(emptyState(state.tasks.length ? 'No hay tareas con este filtro' : 'Este usuario no tiene tareas', state.tasks.length ? 'Selecciona otro filtro para ver las demás tareas.' : 'Usa Nueva tarea para agregar una.', !state.tasks.length ? { label: '+ Nueva tarea', run: newTask } : null));
    }
    for (const task of tasks) {
        const row = element('article', `task-row${task.completed ? ' done' : ''}`);
        const complete = element('button', 'complete-button', task.completed ? '✓' : '');
        complete.disabled = task.completed;
        complete.setAttribute('aria-label', task.completed ? `Completada: ${task.title}` : `Completar: ${task.title}`);
        complete.addEventListener('click', () => completeTask(task, complete));
        const copy = element('div', 'task-copy');
        const meta = element('div', 'task-meta');
        const date = element('time', '', new Intl.DateTimeFormat('es-MX', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(task.created_at)));
        date.dateTime = task.created_at;
        meta.append(element('span', 'badge', task.completed ? 'Completada' : 'Pendiente'), date);
        copy.append(element('h3', 'task-title', task.title), element('p', 'task-description', task.description), meta);
        const remove = element('button', 'delete-button', '×');
        remove.setAttribute('aria-label', `Eliminar: ${task.title}`);
        remove.title = 'Eliminar tarea';
        remove.addEventListener('click', () => {
            deleteId = task.id;
            openDialog('#delete-dialog');
            $('#delete-description').textContent = task.title;
        });
        row.append(complete, copy, remove);
        container.append(row);
    }
}

async function loadUsers() {
    const currentGeneration = generation;
    const { data } = await api.request('/users');
    if (currentGeneration !== generation) return;
    state.users = data;
    renderUsers();
}

async function selectUser(id) {
    taskRequest?.abort();
    const controller = new AbortController();
    taskRequest = controller;
    state.selectedId = id;
    state.tasks = [];
    state.loaded = false;
    state.loadError = null;
    $('#task-error').hidden = true;
    renderUsers();
    renderTasks();
    try {
        const { data } = await api.request(`/users/${id}/tasks`, { signal: controller.signal });
        if (controller.signal.aborted) return;
        state.tasks = data;
        state.loaded = true;
        renderTasks();
    } catch (error) {
        if (error.name === 'AbortError' || handleError(error)) return;
        state.loadError = error.message;
        renderTasks();
        $('#task-error').textContent = error.message;
        $('#task-error').hidden = false;
    }
}

function updateCounts(userId, totalDelta, completedDelta) {
    const user = state.users.find(user => user.id === userId);
    if (user) {
        user.tasks_count += totalDelta;
        user.completed_tasks_count += completedDelta;
    }
    renderUsers();
}

async function completeTask(task, button) {
    button.disabled = true;
    const currentGeneration = generation;
    try {
        const { data } = await api.request(`/tasks/${task.id}/complete`, { method: 'PATCH' });
        if (currentGeneration !== generation) return;
        updateCounts(task.user_id, 0, 1);
        if (state.selectedId === task.user_id) {
            state.tasks = state.tasks.map(item => item.id === task.id ? data : item);
            renderTasks();
        }
        notify('Tarea completada.');
    } catch (error) {
        if (!handleError(error)) notify(error.message);
        button.disabled = false;
    }
}

function newTask() {
    if (!state.selectedId) return;
    openDialog('#task-dialog');
    $('#task-assignee').textContent = state.users.find(user => user.id === state.selectedId).name;
}

function bindForm(selector, operation) {
    const form = $(selector);
    form.addEventListener('submit', async event => {
        event.preventDefault();
        const submit = form.querySelector('[type="submit"]');
        if (submit.disabled) return;
        const dialog = form.closest('dialog');
        const onCancel = event => event.preventDefault();
        dialog.addEventListener('cancel', onCancel);
        form.querySelector('.form-error').hidden = true;
        const buttons = [...form.querySelectorAll('button')];
        buttons.forEach(button => { button.disabled = true; });
        try {
            await operation(Object.fromEntries(new FormData(form)));
        } catch (error) {
            if (!handleError(error)) formError(form, error);
        } finally {
            buttons.forEach(button => { button.disabled = false; });
            dialog.removeEventListener('cancel', onCancel);
        }
    });
}

async function connectAccess(token) {
    api.token = token.trim();
    try {
        await api.request('/me');
        await loadUsers();
    } catch (error) {
        api.token = '';
        throw error;
    }
    $('#access-dialog').close();
    $('#token').value = '';
    $('#add-user').disabled = false;
    $('#user-search').disabled = false;
    $('#logout').hidden = false;
    $('#connection-dot').classList.add('online');
    $('#connection-label').textContent = 'Sesión activa';
    if (state.users.length) await selectUser(state.users[0].id);
    else renderTasks();
    if (api.token) rememberAccess(api.token);
}

bindForm('#access-form', ({ token }) => connectAccess(token));

bindForm('#task-form', async data => {
    const userId = state.selectedId;
    const { data: task } = await api.request(`/users/${userId}/tasks`, { method: 'POST', body: data });
    state.tasks.unshift(task);
    updateCounts(userId, 1, 0);
    state.filter = 'all';
    renderTasks();
    $('#task-dialog').close();
    notify('Tarea creada.');
});

bindForm('#user-form', async data => {
    const { data: user } = await api.request('/users', { method: 'POST', body: data });
    state.users.push({ ...user, tasks_count: 0, completed_tasks_count: 0 });
    state.users.sort((a, b) => a.name.localeCompare(b.name, 'es'));
    $('#user-search').value = '';
    $('#user-dialog').close();
    await selectUser(user.id);
    notify('Usuario creado.');
});

bindForm('#delete-form', async () => {
    const task = state.tasks.find(task => task.id === deleteId);
    await api.request(`/tasks/${deleteId}`, { method: 'DELETE' });
    state.tasks = state.tasks.filter(task => task.id !== deleteId);
    updateCounts(task.user_id, -1, task.completed ? -1 : 0);
    renderTasks();
    $('#delete-dialog').close();
    notify('Tarea eliminada.');
});

document.querySelectorAll('[data-close]').forEach(button => button.addEventListener('click', () => button.closest('dialog').close()));
document.querySelectorAll('[data-filter]').forEach(button => button.addEventListener('click', () => {
    state.filter = button.dataset.filter;
    renderTasks();
}));
$('#sort').addEventListener('change', event => { state.sort = event.target.value; renderTasks(); });
$('#user-search').addEventListener('input', renderUsers);
$('#new-task').addEventListener('click', newTask);
$('#add-user').addEventListener('click', () => openDialog('#user-dialog'));
$('#open-access').addEventListener('click', () => openDialog('#access-dialog'));
$('#logout').addEventListener('click', async () => {
    $('#logout').disabled = true;
    try {
        await api.request('/token', { method: 'DELETE' });
        resetAccess();
        notify('Sesión cerrada.');
    } catch (error) {
        if (!handleError(error)) notify(error.message);
    } finally {
        $('#logout').disabled = false;
    }
});

async function restoreAccess() {
    const token = readAccess();
    if (!token) {
        openDialog('#access-dialog');
        return;
    }
    $('#connection-label').textContent = 'Iniciando sesión…';
    try {
        await connectAccess(token);
    } catch (error) {
        if (!handleError(error)) {
            $('#connection-label').textContent = 'No se pudo conectar';
            openDialog('#access-dialog');
            $('#token').value = token;
            formError($('#access-form'), error);
        }
    }
}

restoreAccess();