import { getAuthToken, removeAuthToken } from '@/services/authToken';

export type ValidationErrors = Record<string, string[]>;

export class ApiError extends Error {
    constructor(
        message: string,
        public readonly status: number,
        public readonly errors: ValidationErrors = {},
    ) {
        super(message);

        this.name = 'ApiError';
    }
}

type ApiErrorResponse = {
    message?: string;
    errors?: ValidationErrors;
};

function defaultErrorMessage(status: number): string {
    if (status >= 500) {
        return 'An unexpected server error occurred. Please try again.';
    }

    return `Request failed (${status}).`;
}

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

        let message = defaultErrorMessage(response.status);
        let errors: ValidationErrors = {};

        try {
            const data = (await response.json()) as ApiErrorResponse;

            if (response.status < 500) {
                if (data.message) {
                    message = data.message;
                }

                if (data.errors) {
                    errors = data.errors;
                }
            }
        } catch {
            //
        }

        throw new ApiError(message, response.status, errors);
    }

    if (response.status === 204) {
        return undefined as T;
    }

    return (await response.json()) as T;
}
