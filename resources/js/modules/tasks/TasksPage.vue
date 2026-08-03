<template>
    <div>
        <PageHeader title="Tasks" description="Track team tasks and assignments" />

        <DataTable
            :items="tasks"
            :columns="columns"
            :loading="loading"
            :pagination="pagination"
            empty-title="No tasks"
            @page-change="fetch"
        >
            <template #row="{ item: task }">
                <td class="px-4 py-3 font-medium">{{ task.title }}</td>
                <td class="px-4 py-3 text-muted-foreground">{{ task.assignee?.name ?? '—' }}</td>
                <td class="px-4 py-3 capitalize text-muted-foreground">{{ task.priority ?? '—' }}</td>
                <td class="px-4 py-3 text-muted-foreground">{{ formatDate(task.due_at) }}</td>
                <td class="px-4 py-3"><StatusBadge :status="task.status" /></td>
                <td class="px-4 py-3">
                    <Button v-if="task.status !== 'completed'" size="sm" variant="outline" @click="complete(task.id)">
                        Complete
                    </Button>
                </td>
            </template>
        </DataTable>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { apiGet, apiPost } from '@/lib/api';
import { formatDate } from '@/lib/utils';
import type { Task, Paginated, PaginationMeta } from '@/types';
import PageHeader from '@/components/shared/PageHeader.vue';
import DataTable from '@/components/shared/DataTable.vue';
import StatusBadge from '@/components/shared/StatusBadge.vue';
import Button from '@/components/ui/Button.vue';
import type { TableColumn } from '@/components/ui/Table.vue';

const tasks = ref<Task[]>([]);
const loading = ref(false);
const pagination = ref<PaginationMeta | null>(null);

const columns: TableColumn[] = [
    { key: 'title', label: 'Title' },
    { key: 'assignee', label: 'Assignee' },
    { key: 'priority', label: 'Priority' },
    { key: 'due_at', label: 'Due' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: '' },
];

async function fetch(page = 1): Promise<void> {
    loading.value = true;
    try {
        const response = await apiGet<Paginated<Task>>('/tasks', { params: { page, per_page: 15 } });
        tasks.value = response.data;
        pagination.value = response.meta;
    } finally {
        loading.value = false;
    }
}

async function complete(id: number): Promise<void> {
    await apiPost(`/tasks/${id}/complete`);
    await fetch();
}

onMounted(() => fetch());
</script>
