<template>
    <div class="space-y-4">
        <div v-if="loading" class="flex items-center justify-center py-12">
            <LoadingSpinner class="h-8 w-8" />
        </div>
        <EmptyState
            v-else-if="!items.length"
            :title="emptyTitle"
            :description="emptyDescription"
        >
            <template v-if="$slots.empty" #action>
                <slot name="empty" />
            </template>
        </EmptyState>
        <UiTable v-else :columns="columns">
            <tr
                v-for="(item, index) in items"
                :key="getKey(item, index)"
                class="border-b border-border transition-colors hover:bg-muted/50"
                :class="{ 'cursor-pointer': clickable }"
                @click="clickable && $emit('row-click', item)"
            >
                <slot name="row" :item="item" />
            </tr>
        </UiTable>
        <div v-if="pagination && pagination.last_page > 1" class="flex items-center justify-between text-sm text-muted-foreground">
            <span>Showing {{ pagination.from }}–{{ pagination.to }} of {{ pagination.total }}</span>
            <div class="flex gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="pagination.current_page <= 1"
                    @click="$emit('page-change', pagination.current_page - 1)"
                >
                    Previous
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="pagination.current_page >= pagination.last_page"
                    @click="$emit('page-change', pagination.current_page + 1)"
                >
                    Next
                </Button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import UiTable, { type TableColumn } from '@/components/ui/Table.vue';
import Button from '@/components/ui/Button.vue';
import EmptyState from '@/components/shared/EmptyState.vue';
import LoadingSpinner from '@/components/shared/LoadingSpinner.vue';
import type { PaginationMeta } from '@/types';

const props = withDefaults(defineProps<{
    items: Record<string, unknown>[];
    columns: TableColumn[];
    loading?: boolean;
    pagination?: PaginationMeta | null;
    emptyTitle?: string;
    emptyDescription?: string;
    clickable?: boolean;
    rowKey?: string;
}>(), {
    loading: false,
    clickable: false,
    rowKey: 'id',
});

defineEmits<{
    'page-change': [page: number];
    'row-click': [item: Record<string, unknown>];
}>();

function getKey(item: Record<string, unknown>, index: number): string | number {
    const key = item[props.rowKey];
    return typeof key === 'number' || typeof key === 'string' ? key : index;
}
</script>
