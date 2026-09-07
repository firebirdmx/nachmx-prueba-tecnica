export function selectTasks(tasks, filter = 'all', sort = 'date-desc') {
    const selected = tasks.filter(task => filter === 'all' || task.completed === (filter === 'completed'));
    return selected.sort((a, b) => {
        const result = sort.startsWith('title')
            ? a.title.localeCompare(b.title, 'es', { sensitivity: 'base' })
            : new Date(a.created_at).getTime() - new Date(b.created_at).getTime();
        return (sort.endsWith('desc') ? -result : result) || (sort.endsWith('desc') ? b.id - a.id : a.id - b.id);
    });
}

export function summarizeTasks(tasks) {
    const completed = tasks.filter(task => task.completed).length;
    return { total: tasks.length, completed, pending: tasks.length - completed };
}

export function initials(name) {
    return name.trim().split(/\s+/u).slice(0, 2).map(part => Array.from(part)[0] || '').join('').toLocaleUpperCase('es');
}
