const tokenKey = 'fleettrack_auth_token';

export function getAuthToken(): string | null {
    return localStorage.getItem(tokenKey);
}

export function setAuthToken(token: string): void {
    localStorage.setItem(tokenKey, token);
}

export function removeAuthToken(): void {
    localStorage.removeItem(tokenKey);
}
