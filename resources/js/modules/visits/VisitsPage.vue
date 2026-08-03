<template>
    <div>
        <PageHeader title="Visits" description="Schedule and track site visits">
            <template #actions>
                <Button @click="showSchedule = true">
                    <Plus class="h-4 w-4" />
                    Schedule Visit
                </Button>
            </template>
        </PageHeader>

        <DataTable
            :items="visits"
            :columns="columns"
            :loading="loading"
            :pagination="pagination"
            empty-title="No visits scheduled"
            @page-change="fetch"
        >
            <template #row="{ item: visit }">
                <td class="px-4 py-3 font-medium">{{ visit.lead?.name ?? visit.customer?.name ?? '—' }}</td>
                <td class="px-4 py-3 text-muted-foreground">{{ visit.project?.name ?? '—' }}</td>
                <td class="px-4 py-3 text-muted-foreground">{{ formatDateTime(visit.scheduled_at) }}</td>
                <td class="px-4 py-3"><StatusBadge :status="visit.status" /></td>
                <td class="px-4 py-3 text-muted-foreground">{{ visit.assignee?.name ?? '—' }}</td>
            </template>
        </DataTable>

        <Modal :open="showSchedule" title="Schedule Visit" @close="showSchedule = false">
            <form class="space-y-4" @submit.prevent="handleSchedule">
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Title / Notes</label>
                    <Input v-model="form.notes" placeholder="Visit notes" />
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Scheduled At *</label>
                    <Input v-model="form.scheduled_at" type="datetime-local" required />
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Location</label>
                    <Input v-model="form.location" />
                </div>
            </form>
            <template #footer>
                <Button variant="outline" @click="showSchedule = false">Cancel</Button>
                <Button :loading="scheduling" @click="handleSchedule">Schedule</Button>
            </template>
        </Modal>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { Plus } from 'lucide-vue-next';
import { apiGet, apiPost } from '@/lib/api';
import { formatDateTime } from '@/lib/utils';
import type { Visit, Paginated, PaginationMeta } from '@/types';
import PageHeader from '@/components/shared/PageHeader.vue';
import DataTable from '@/components/shared/DataTable.vue';
import StatusBadge from '@/components/shared/StatusBadge.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import Modal from '@/components/ui/Modal.vue';
import type { TableColumn } from '@/components/ui/Table.vue';

const visits = ref<Visit[]>([]);
const loading = ref(false);
const pagination = ref<PaginationMeta | null>(null);
const showSchedule = ref(false);
const scheduling = ref(false);

const form = reactive({ notes: '', scheduled_at: '', location: '' });

const columns: TableColumn[] = [
    { key: 'contact', label: 'Contact' },
    { key: 'project', label: 'Project' },
    { key: 'scheduled_at', label: 'Scheduled' },
    { key: 'status', label: 'Status' },
    { key: 'assignee', label: 'Assignee' },
];

async function fetch(page = 1): Promise<void> {
    loading.value = true;
    try {
        const response = await apiGet<Paginated<Visit>>('/visits', { params: { page, per_page: 15 } });
        visits.value = response.data;
        pagination.value = response.meta;
    } finally {
        loading.value = false;
    }
}

async function handleSchedule(): Promise<void> {
    scheduling.value = true;
    try {
        await apiPost('/visits', form);
        showSchedule.value = false;
        await fetch();
    } finally {
        scheduling.value = false;
    }
}

onMounted(() => fetch());
</script>
