import { getAuthToken, removeAuthToken } from '@/services/authToken';

export class ApiError extends Error {
    constructor(
        message: string,
        public readonly status: number,
    ) {
        super(message);

        this.name = 'ApiError';
    }
}

type ApiErrorResponse = {
    message?: string;
};

export async function apiRequest<T>(
    input: string,
    init: RequestInit = {},
): Promise<T> {
    const token = getAuthToken();

    const headers = new Headers(init.headers);

    headers.set('Accept', 'application/json');

    if (token) {
        headers.set('Authorization', `Bearer ${token}`);
    }

    const response = await fetch(input, {
        ...init,
        headers,
    });

    if (!response.ok) {
        if (response.status === 401) {
            removeAuthToken();
        }

        let message = `Request failed (${response.status}).`;

        try {
            const data = (await response.json()) as ApiErrorResponse;

            if (data.message) {
                message = data.message;
            }
        } catch {
            //
        }

        throw new ApiError(message, response.status);
    }

    if (response.status === 204) {
        return undefined as T;
    }

    return (await response.json()) as T;
}
