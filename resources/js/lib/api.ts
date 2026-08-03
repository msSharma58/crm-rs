import axios, { type AxiosError, type AxiosRequestConfig } from 'axios';
import type { ApiResponse } from '@/types';

const api = axios.create({
    baseURL: '/api/v1',
    withCredentials: true,
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
    xsrfCookieName: 'XSRF-TOKEN',
    xsrfHeaderName: 'X-XSRF-TOKEN',
});

api.interceptors.request.use((config) => {
    const token = localStorage.getItem('auth_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

api.interceptors.response.use(
    (response) => response,
    (error: AxiosError<ApiResponse<unknown>>) => {
        if (error.response?.status === 401) {
            localStorage.removeItem('auth_token');
        }
        return Promise.reject(error);
    },
);

export async function getCsrfCookie(): Promise<void> {
    await axios.get('/sanctum/csrf-cookie', { withCredentials: true });
}

export async function apiGet<T>(url: string, config?: AxiosRequestConfig): Promise<T> {
    const response = await api.get<ApiResponse<T>>(url, config);
    return response.data.data;
}

export async function apiPost<T>(url: string, data?: unknown, config?: AxiosRequestConfig): Promise<T> {
    const response = await api.post<ApiResponse<T>>(url, data, config);
    return response.data.data;
}

export async function apiPut<T>(url: string, data?: unknown, config?: AxiosRequestConfig): Promise<T> {
    const response = await api.put<ApiResponse<T>>(url, data, config);
    return response.data.data;
}

export async function apiPatch<T>(url: string, data?: unknown, config?: AxiosRequestConfig): Promise<T> {
    const response = await api.patch<ApiResponse<T>>(url, data, config);
    return response.data.data;
}

export async function apiDelete<T>(url: string, config?: AxiosRequestConfig): Promise<T> {
    const response = await api.delete<ApiResponse<T>>(url, config);
    return response.data.data;
}

export async function apiUpload<T>(url: string, formData: FormData, config?: AxiosRequestConfig): Promise<T> {
    const response = await api.post<ApiResponse<T>>(url, formData, {
        ...config,
        headers: {
            ...config?.headers,
            'Content-Type': undefined,
        },
    });
    return response.data.data;
}

export async function downloadAuthenticated(url: string, filename: string): Promise<void> {
    const token = localStorage.getItem('auth_token');
    const response = await fetch(`/api/v1${url.startsWith('/') ? url : `/${url}`}`, {
        headers: {
            Accept: 'text/csv,application/octet-stream',
            ...(token ? { Authorization: `Bearer ${token}` } : {}),
        },
        credentials: 'include',
    });

    if (!response.ok) {
        throw new Error(`Download failed: ${response.statusText}`);
    }

    const blob = await response.blob();
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(link.href);
}

export function getApiError(error: unknown): string {
    if (axios.isAxiosError(error)) {
        const axiosError = error as AxiosError<ApiResponse<unknown> & { errors?: Record<string, string[]> }>;
        if (axiosError.response?.data?.errors) {
            const errors = axiosError.response.data.errors;
            const firstKey = Object.keys(errors)[0];
            if (firstKey && errors[firstKey]?.[0]) {
                return errors[firstKey][0];
            }
        }
        return axiosError.response?.data?.message ?? axiosError.message ?? 'An error occurred';
    }
    return 'An unexpected error occurred';
}

export default api;
