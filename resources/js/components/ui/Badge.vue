<template>
    <span :class="cn('inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', variants[variant], props.class)">
        <slot />
    </span>
</template>

<script setup lang="ts">
import { cn } from '@/lib/utils';

type Variant = 'default' | 'secondary' | 'success' | 'warning' | 'destructive' | 'outline';

const props = withDefaults(defineProps<{
    variant?: Variant;
    class?: string;
}>(), {
    variant: 'default',
});

const variants: Record<Variant, string> = {
    default: 'bg-brand-500/10 text-brand-600 dark:text-brand-400',
    secondary: 'bg-muted text-muted-foreground',
    success: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
    warning: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
    destructive: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
    outline: 'border border-border text-foreground',
};
</script>
