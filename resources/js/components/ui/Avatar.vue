<template>
    <div
        :class="cn(
            'flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-500/10 text-sm font-medium text-brand-600 dark:text-brand-400',
            size === 'lg' && 'h-12 w-12 text-base',
            size === 'sm' && 'h-7 w-7 text-xs',
            props.class,
        )"
    >
        <img v-if="src" :src="src" :alt="name" class="h-full w-full rounded-full object-cover" />
        <span v-else>{{ initials }}</span>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { cn, getInitials } from '@/lib/utils';

const props = withDefaults(defineProps<{
    name: string;
    src?: string | null;
    size?: 'sm' | 'md' | 'lg';
    class?: string;
}>(), {
    size: 'md',
});

const initials = computed(() => getInitials(props.name));
</script>
