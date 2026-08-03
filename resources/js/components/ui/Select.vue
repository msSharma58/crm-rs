<template>
    <select
        :value="modelValue"
        :disabled="disabled"
        :class="cn(
            'flex h-9 w-full rounded-lg border border-border bg-card px-3 py-1 text-sm text-foreground shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50',
            props.class,
        )"
        @change="$emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
    >
        <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
        <slot />
    </select>
</template>

<script setup lang="ts">
import { cn } from '@/lib/utils';

const props = withDefaults(defineProps<{
    modelValue?: string | number;
    placeholder?: string;
    disabled?: boolean;
    class?: string;
}>(), {
    modelValue: '',
});

defineEmits<{
    'update:modelValue': [value: string];
    change: [];
}>();
</script>
