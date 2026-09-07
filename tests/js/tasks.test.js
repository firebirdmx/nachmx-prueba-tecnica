import test from 'node:test';
import assert from 'node:assert/strict';
import { selectTasks, summarizeTasks, initials } from '../../public/js/tasks.js';
import { ApiClient, ApiError } from '../../public/js/api.js';

const tasks = [
    { id: 1, title: 'Zeta', completed: false, created_at: '2026-01-02T12:00:00Z' },
    { id: 2, title: 'Árbol', completed: true, created_at: '2026-01-01T12:00:00Z' },
    { id: 3, title: 'Beta', completed: false, created_at: '2026-01-03T12:00:00Z' },
];
test('filtros de estado y conteos independientes del filtro', () => {
    assert.deepEqual(selectTasks(tasks, 'pending').map(t => t.id), [3, 1]);
    assert.deepEqual(selectTasks(tasks, 'completed').map(t => t.id), [2]);
    assert.deepEqual(summarizeTasks(tasks), { total: 3, completed: 1, pending: 2 });
    assert.deepEqual(summarizeTasks([]), { total: 0, completed: 0, pending: 0 });
});
test('ordena por título en español y fecha en ambas direcciones sin mutar el original', () => {
    assert.deepEqual(selectTasks(tasks, 'all', 'title-asc').map(t => t.id), [2, 3, 1]);
    assert.deepEqual(selectTasks(tasks, 'all', 'title-desc').map(t => t.id), [1, 3, 2]);
    assert.deepEqual(selectTasks(tasks, 'all', 'date-asc').map(t => t.id), [2, 1, 3]);
    assert.deepEqual(selectTasks(tasks).map(t => t.id), [3, 1, 2]);
    assert.deepEqual(tasks.map(t => t.id), [1, 2, 3]);
});
test('iniciales con espacios y acentos', () => {
    assert.equal(initials('  Ana   García  '), 'AG');
    assert.equal(initials('Érika'), 'É');
    assert.equal(initials(''), '');
});
test('fetch envía token Bearer, JSON y propaga validaciones 422', async t => {
    const api = new ApiClient('http://localhost/api');
    api.token = 'test-token';
    t.mock.method(globalThis, 'fetch', async (url, options) => {
        assert.equal(url, 'http://localhost/api/users');
        assert.equal(options.headers.Authorization, 'Bearer test-token');
        assert.equal(options.credentials, 'omit');
        assert.equal(JSON.parse(options.body).name, 'Ana');
        return new Response(JSON.stringify({ message: 'Invalid', errors: { email: ['Correo inválido'] } }), { status: 422 });
    });
    await assert.rejects(api.request('/users', { method: 'POST', body: { name: 'Ana' } }),
        error => error instanceof ApiError && error.status === 422 && error.errors.email[0] === 'Correo inválido');
});
test('error de red y respuesta no JSON se convierten en errores legibles', async t => {
    const api = new ApiClient('');
    const fetch = t.mock.method(globalThis, 'fetch', async () => { throw new TypeError('network'); });
    await assert.rejects(api.request('/users'), /No se pudo conectar/);
    fetch.mock.mockImplementation(async () => new Response('<html>oops</html>', { status: 500 }));
    await assert.rejects(api.request('/users'), error => error.status === 500);
});
