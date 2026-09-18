export type User = {
    id: number;
    company_id: number;
    name: string;
    email: string;
    roles: string[];
    is_active: boolean;
    last_login_at: string | null;
    created_at: string;
    updated_at: string;
};

export type LoginCredentials = {
    email: string;
    password: string;
    device_name?: string;
};

export type LoginResponse = {
    token: string;
    token_type: 'Bearer';
    user: User;
};

export type UserResponse = {
    data: User;
};
