<template>
    <div :class="cn('rounded-xl border border-border bg-card text-card-foreground shadow-sm', props.class)">
        <div v-if="$slots.header" class="flex flex-col space-y-1.5 p-6 pb-0">
            <slot name="header" />
        </div>
        <div :class="cn('p-6', { 'pt-0': $slots.header })">
            <slot />
        </div>
        <div v-if="$slots.footer" class="flex items-center p-6 pt-0">
            <slot name="footer" />
        </div>
    </div>
</template>

<script setup lang="ts">
import { cn } from '@/lib/utils';

const props = defineProps<{ class?: string }>();
</script>
