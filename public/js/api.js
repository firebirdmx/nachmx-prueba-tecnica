export class ApiError extends Error {
    constructor(message, status = 0, errors = {}) {
        super(message);
        this.status = status;
        this.errors = errors;
    }
}

export class ApiClient {
    constructor(baseUrl) {
        this.baseUrl = baseUrl;
        this.token = '';
    }

    async request(path, { method = 'GET', body, signal } = {}) {
        let response;
        try {
            response = await fetch(`${this.baseUrl}${path}`, {
                method,
                signal,
                credentials: 'omit',
                headers: {
                    Accept: 'application/json',
                    Authorization: `Bearer ${this.token}`,
                    ...(body !== undefined ? { 'Content-Type': 'application/json' } : {}),
                },
                ...(body !== undefined ? { body: JSON.stringify(body) } : {}),
            });
        } catch (error) {
            if (error.name === 'AbortError') throw error;
            throw new ApiError('No se pudo conectar con el servidor. Revisa tu conexión e intenta de nuevo.');
        }
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) {
            throw new ApiError(payload.message || 'Ocurrió un error. Intenta de nuevo.', response.status, payload.errors);
        }
        return payload;
    }
}
