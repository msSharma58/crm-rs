<template>
    <div>
        <PageHeader title="Tasks" description="Track team tasks and assignments">
            <template #actions>
                <Button @click="showCreate = true">New Task</Button>
            </template>
        </PageHeader>

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

        <Modal :open="showCreate" title="Create Task" @close="showCreate = false">
            <form class="space-y-4" @submit.prevent="handleCreate">
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Title *</label>
                    <Input v-model="form.title" required />
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Description</label>
                    <Input v-model="form.description" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Priority</label>
                        <Select v-model="form.priority">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </Select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Due Date</label>
                        <Input v-model="form.due_at" type="datetime-local" />
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Assign To</label>
                    <Select v-model="form.assigned_to">
                        <option value="">Unassigned</option>
                        <option v-for="u in users" :key="u.id" :value="String(u.id)">{{ u.name }}</option>
                    </Select>
                </div>
                <div v-if="createError" class="text-sm text-red-600">{{ createError }}</div>
            </form>
            <template #footer>
                <Button variant="outline" @click="showCreate = false">Cancel</Button>
                <Button :loading="creating" @click="handleCreate">Create</Button>
            </template>
        </Modal>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { apiGet, apiPost, getApiError } from '@/lib/api';
import { formatDate } from '@/lib/utils';
import { useToast } from '@/composables/useToast';
import type { Task, User, Paginated, PaginationMeta } from '@/types';
import PageHeader from '@/components/shared/PageHeader.vue';
import DataTable from '@/components/shared/DataTable.vue';
import StatusBadge from '@/components/shared/StatusBadge.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import Select from '@/components/ui/Select.vue';
import Modal from '@/components/ui/Modal.vue';
import type { TableColumn } from '@/components/ui/Table.vue';

const toast = useToast();

const tasks = ref<Task[]>([]);
const users = ref<User[]>([]);
const loading = ref(false);
const pagination = ref<PaginationMeta | null>(null);
const showCreate = ref(false);
const creating = ref(false);
const createError = ref('');

const form = reactive({
    title: '',
    description: '',
    priority: 'medium',
    due_at: '',
    assigned_to: '',
});

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

async function handleCreate(): Promise<void> {
    creating.value = true;
    createError.value = '';
    try {
        const payload: Record<string, unknown> = {
            title: form.title,
            priority: form.priority,
        };
        if (form.description) payload.description = form.description;
        if (form.due_at) payload.due_at = form.due_at;
        if (form.assigned_to) payload.assigned_to = Number(form.assigned_to);
        await apiPost('/tasks', payload);
        showCreate.value = false;
        toast.success('Task created');
        Object.assign(form, { title: '', description: '', priority: 'medium', due_at: '', assigned_to: '' });
        await fetch();
    } catch (e) {
        createError.value = getApiError(e);
    } finally {
        creating.value = false;
    }
}

async function complete(id: number): Promise<void> {
    try {
        await apiPost(`/tasks/${id}/complete`);
        toast.success('Task completed');
        await fetch();
    } catch (e) {
        toast.error(getApiError(e));
    }
}

onMounted(async () => {
    await fetch();
    const usersRes = await apiGet<Paginated<User>>('/users', { params: { per_page: 100 } });
    users.value = usersRes.data;
});
</script>
