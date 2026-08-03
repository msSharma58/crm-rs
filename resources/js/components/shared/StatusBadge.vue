<template>
    <span :class="cn('inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', colorClass)">
        {{ label }}
    </span>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import { LEAD_STATUS_LABELS, LEAD_STATUS_COLORS, type LeadStatus } from '@/types/lead';

const props = defineProps<{
    status: string;
    type?: 'lead' | 'generic';
}>();

const label = computed(() => {
    if (props.type === 'lead' && props.status in LEAD_STATUS_LABELS) {
        return LEAD_STATUS_LABELS[props.status as LeadStatus];
    }
    return props.status.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
});

const colorClass = computed(() => {
    if (props.type === 'lead' && props.status in LEAD_STATUS_COLORS) {
        return LEAD_STATUS_COLORS[props.status as LeadStatus];
    }
    const map: Record<string, string> = {
        pending: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
        completed: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
        active: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
        cancelled: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
        available: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
        reserved: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
        sold: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
    };
    return map[props.status] ?? 'bg-muted text-muted-foreground';
});
</script>
