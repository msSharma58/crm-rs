<template>
    <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-border py-16 text-center">
        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-muted">
            <component :is="icon" class="h-6 w-6 text-muted-foreground" />
        </div>
        <h3 class="text-base font-medium text-foreground">{{ title }}</h3>
        <p v-if="description" class="mt-1 max-w-sm text-sm text-muted-foreground">{{ description }}</p>
        <div v-if="$slots.action" class="mt-4">
            <slot name="action" />
        </div>
    </div>
</template>

<script setup lang="ts">
import { Inbox } from 'lucide-vue-next';
import type { Component } from 'vue';

withDefaults(defineProps<{
    title: string;
    description?: string;
    icon?: Component;
}>(), {
    icon: Inbox,
});
</script>
