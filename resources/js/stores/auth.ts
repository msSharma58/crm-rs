import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { apiGet, apiPost, getApiError, getCsrfCookie } from '@/lib/api';
import type { LoginResponse, User } from '@/types';

export const useAuthStore = defineStore('auth', () => {
    const user = ref<User | null>(null);
    const token = ref<string | null>(localStorage.getItem('auth_token'));
    const loading = ref(false);
    const initialized = ref(false);

    const isAuthenticated = computed(() => !!token.value && !!user.value);
    const organization = computed(() => user.value?.organization ?? null);
    const permissions = computed(() => {
        const roles = user.value?.roles ?? [];
        return roles;
    });

    async function login(email: string, password: string): Promise<void> {
        loading.value = true;
        try {
            await getCsrfCookie();
            const response = await apiPost<LoginResponse>('/auth/login', {
                email,
                password,
                device_name: 'web',
            });
            token.value = response.token;
            user.value = response.user;
            localStorage.setItem('auth_token', response.token);
        } finally {
            loading.value = false;
        }
    }

    async function fetchMe(): Promise<void> {
        if (!token.value) {
            initialized.value = true;
            return;
        }
        loading.value = true;
        try {
            user.value = await apiGet<User>('/auth/me');
        } catch {
            token.value = null;
            user.value = null;
            localStorage.removeItem('auth_token');
        } finally {
            loading.value = false;
            initialized.value = true;
        }
    }

    async function logout(): Promise<void> {
        try {
            await apiPost('/auth/logout');
        } catch {
            // ignore logout errors
        } finally {
            token.value = null;
            user.value = null;
            localStorage.removeItem('auth_token');
        }
    }

    function getErrorMessage(error: unknown): string {
        return getApiError(error);
    }

    return {
        user,
        token,
        loading,
        initialized,
        isAuthenticated,
        organization,
        permissions,
        login,
        fetchMe,
        logout,
        getErrorMessage,
    };
});
