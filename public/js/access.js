const storageKey = 'taskdesk.access';

// Recupera el acceso al recargar. La API comprueba que el token siga vigente.
export function readAccess() {
    try { return sessionStorage.getItem(storageKey) || ''; }
    catch { return ''; }
}

export function rememberAccess(token) {
    try { sessionStorage.setItem(storageKey, token); }
    catch { /* Si el navegador bloquea el almacenamiento, el token queda solo en memoria. */ }
}

export function forgetAccess() {
    try { sessionStorage.removeItem(storageKey); }
    catch { /* El navegador puede bloquear el almacenamiento. */ }
}