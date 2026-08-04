import { ref, readonly } from 'vue';

export type ToastType = 'success' | 'error' | 'info';

export interface Toast {
    id: number;
    type: ToastType;
    message: string;
}

const toasts = ref<Toast[]>([]);
let nextId = 0;

const DEFAULT_DURATION = 4000;

function removeToast(id: number): void {
    const idx = toasts.value.findIndex((t) => t.id === id);
    if (idx !== -1) {
        toasts.value.splice(idx, 1);
    }
}

function addToast(type: ToastType, message: string, duration = DEFAULT_DURATION): void {
    const id = nextId++;
    toasts.value.push({ id, type, message });
    if (duration > 0) {
        setTimeout(() => removeToast(id), duration);
    }
}

export function useToast() {
    return {
        toasts: readonly(toasts),
        success: (message: string) => addToast('success', message),
        error: (message: string) => addToast('error', message),
        info: (message: string) => addToast('info', message),
        remove: removeToast,
    };
}
