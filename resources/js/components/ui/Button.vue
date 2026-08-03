<template>
    <component
        :is="tag"
        :type="tag === 'button' ? type : undefined"
        :disabled="disabled || loading"
        :class="cn(
            'inline-flex items-center justify-center gap-2 rounded-lg font-medium transition-all duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50',
            variants[variant],
            sizes[size],
            props.class,
        )"
        v-bind="$attrs"
    >
        <LoadingSpinner v-if="loading" class="h-4 w-4" />
        <slot />
    </component>
</template>

<script setup lang="ts">
import { cn } from '@/lib/utils';
import LoadingSpinner from '@/components/shared/LoadingSpinner.vue';

type Variant = 'default' | 'secondary' | 'outline' | 'ghost' | 'destructive';
type Size = 'sm' | 'md' | 'lg' | 'icon';

const props = withDefaults(defineProps<{
    variant?: Variant;
    size?: Size;
    type?: 'button' | 'submit' | 'reset';
    disabled?: boolean;
    loading?: boolean;
    tag?: 'button' | 'a';
    class?: string;
}>(), {
    variant: 'default',
    size: 'md',
    type: 'button',
    disabled: false,
    loading: false,
    tag: 'button',
});

const variants: Record<Variant, string> = {
    default: 'bg-brand-500 text-white hover:bg-brand-600 shadow-sm',
    secondary: 'bg-muted text-foreground hover:bg-accent',
    outline: 'border border-border bg-card hover:bg-accent text-foreground',
    ghost: 'hover:bg-accent text-foreground',
    destructive: 'bg-red-600 text-white hover:bg-red-700',
};

const sizes: Record<Size, string> = {
    sm: 'h-8 px-3 text-xs',
    md: 'h-9 px-4 text-sm',
    lg: 'h-11 px-6 text-sm',
    icon: 'h-9 w-9',
};
</script>
