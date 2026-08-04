<template>
    <div class="fixed right-4 top-4 z-[100] flex flex-col gap-2 max-w-sm w-full pointer-events-none">
        <TransitionGroup
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="translate-x-4 opacity-0"
            enter-to-class="translate-x-0 opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="translate-x-0 opacity-100"
            leave-to-class="translate-x-4 opacity-0"
        >
            <div
                v-for="toast in toasts"
                :key="toast.id"
                class="pointer-events-auto flex items-start gap-3 rounded-lg border px-4 py-3 shadow-lg"
                :class="toastClasses[toast.type]"
            >
                <p class="flex-1 text-sm font-medium">{{ toast.message }}</p>
                <button
                    type="button"
                    class="shrink-0 rounded p-0.5 opacity-70 hover:opacity-100"
                    aria-label="Dismiss"
                    @click="remove(toast.id)"
                >
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>

<script setup lang="ts">
import { useToast } from '@/composables/useToast';

const { toasts, remove } = useToast();

const toastClasses = {
    success: 'border-emerald-500/30 bg-emerald-50 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200',
    error: 'border-red-500/30 bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200',
    info: 'border-brand-500/30 bg-brand-50 text-brand-800 dark:bg-brand-950 dark:text-brand-200',
};
</script>
