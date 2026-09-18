import { computed, readonly, ref } from 'vue';

import {
    getAuthenticatedUser,
    logout as logoutRequest,
} from '@/services/authService';
import { getAuthToken, removeAuthToken } from '@/services/authToken';
import type { User } from '@/types/auth';

const user = ref<User | null>(null);
const loading = ref(false);
const initialized = ref(false);

const authenticated = computed(
    () => user.value !== null && getAuthToken() !== null,
);

async function initialize(): Promise<void> {
    if (initialized.value) {
        return;
    }

    loading.value = true;

    try {
        if (!getAuthToken()) {
            user.value = null;

            return;
        }

        user.value = await getAuthenticatedUser();
    } catch {
        user.value = null;
        removeAuthToken();
    } finally {
        initialized.value = true;
        loading.value = false;
    }
}

function setUser(authenticatedUser: User): void {
    user.value = authenticatedUser;
    initialized.value = true;
}

async function logout(): Promise<void> {
    try {
        await logoutRequest();
    } finally {
        user.value = null;
        initialized.value = true;
    }
}

export const authState = {
    user: readonly(user),
    loading: readonly(loading),
    initialized: readonly(initialized),
    authenticated,
    initialize,
    setUser,
    logout,
};
