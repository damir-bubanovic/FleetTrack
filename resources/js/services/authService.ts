import { apiRequest } from '@/services/apiClient';
import { removeAuthToken, setAuthToken } from '@/services/authToken';
import type {
    LoginCredentials,
    LoginResponse,
    User,
    UserResponse,
} from '@/types/auth';

export async function login(credentials: LoginCredentials): Promise<User> {
    const response = await fetch('/api/v1/auth/login', {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            ...credentials,
            device_name: credentials.device_name ?? 'FleetTrack Web',
        }),
    });

    if (!response.ok) {
        const data = (await response.json()) as {
            message?: string;
        };

        throw new Error(data.message ?? 'Unable to sign in.');
    }

    const data = (await response.json()) as LoginResponse;

    setAuthToken(data.token);

    return data.user;
}

export async function getAuthenticatedUser(): Promise<User> {
    const data = await apiRequest<UserResponse>('/api/v1/auth/me');

    return data.data;
}

export async function logout(): Promise<void> {
    try {
        await apiRequest<void>('/api/v1/auth/logout', {
            method: 'POST',
        });
    } finally {
        removeAuthToken();
    }
}
