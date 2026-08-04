<template>
    <div>
        <PageHeader title="Visits" description="Schedule and track site visits">
            <template #actions>
                <Button @click="openSchedule">
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
                <td class="px-4 py-3">
                    <div class="flex gap-1">
                        <Button
                            v-if="visit.status === 'scheduled'"
                            size="sm"
                            variant="outline"
                            @click="checkIn(visit.id)"
                        >
                            Check In
                        </Button>
                        <Button
                            v-if="visit.status === 'checked_in'"
                            size="sm"
                            variant="outline"
                            @click="complete(visit.id)"
                        >
                            Complete
                        </Button>
                    </div>
                </td>
            </template>
        </DataTable>

        <Modal :open="showSchedule" title="Schedule Visit" @close="showSchedule = false">
            <form class="space-y-4" @submit.prevent="handleSchedule">
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Assigned To *</label>
                    <Select v-model="form.assigned_to" required>
                        <option value="" disabled>Select user</option>
                        <option v-for="u in users" :key="u.id" :value="String(u.id)">{{ u.name }}</option>
                    </Select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Lead (optional)</label>
                    <Select v-model="form.lead_id">
                        <option value="">None</option>
                        <option v-for="l in leads" :key="l.id" :value="String(l.id)">{{ l.name }}</option>
                    </Select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Project (optional)</label>
                    <Select v-model="form.project_id">
                        <option value="">None</option>
                        <option v-for="p in projects" :key="p.id" :value="String(p.id)">{{ p.name }}</option>
                    </Select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Scheduled At *</label>
                    <Input v-model="form.scheduled_at" type="datetime-local" required />
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Location</label>
                    <Input v-model="form.location" />
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Notes</label>
                    <Input v-model="form.notes" placeholder="Visit notes" />
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
import { Plus } from '@lucide/vue';
import { apiGet, apiPost, getApiError } from '@/lib/api';
import { formatDateTime } from '@/lib/utils';
import { useToast } from '@/composables/useToast';
import { useAuthStore } from '@/stores/auth';
import type { Visit, Lead, Project, User, Paginated, PaginationMeta } from '@/types';
import PageHeader from '@/components/shared/PageHeader.vue';
import DataTable from '@/components/shared/DataTable.vue';
import StatusBadge from '@/components/shared/StatusBadge.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import Select from '@/components/ui/Select.vue';
import Modal from '@/components/ui/Modal.vue';
import type { TableColumn } from '@/components/ui/Table.vue';

const toast = useToast();
const auth = useAuthStore();

const visits = ref<Visit[]>([]);
const users = ref<User[]>([]);
const leads = ref<Lead[]>([]);
const projects = ref<Project[]>([]);
const loading = ref(false);
const pagination = ref<PaginationMeta | null>(null);
const showSchedule = ref(false);
const scheduling = ref(false);

const form = reactive({
    assigned_to: '',
    lead_id: '',
    project_id: '',
    scheduled_at: '',
    location: '',
    notes: '',
});

const columns: TableColumn[] = [
    { key: 'contact', label: 'Contact' },
    { key: 'project', label: 'Project' },
    { key: 'scheduled_at', label: 'Scheduled' },
    { key: 'status', label: 'Status' },
    { key: 'assignee', label: 'Assignee' },
    { key: 'actions', label: '' },
];

function openSchedule(): void {
    form.assigned_to = auth.user ? String(auth.user.id) : '';
    showSchedule.value = true;
}

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

async function loadSelectData(): Promise<void> {
    const [usersRes, leadsRes, projectsRes] = await Promise.all([
        apiGet<Paginated<User>>('/users', { params: { per_page: 100 } }),
        apiGet<Paginated<Lead> | Lead[]>('/leads', { params: { per_page: 100 } }),
        apiGet<Paginated<Project>>('/projects', { params: { per_page: 100 } }),
    ]);
    users.value = usersRes.data;
    leads.value = Array.isArray(leadsRes) ? leadsRes : leadsRes.data;
    projects.value = projectsRes.data;
}

async function handleSchedule(): Promise<void> {
    scheduling.value = true;
    try {
        const payload: Record<string, unknown> = {
            assigned_to: Number(form.assigned_to),
            scheduled_at: form.scheduled_at,
        };
        if (form.lead_id) payload.lead_id = Number(form.lead_id);
        if (form.project_id) payload.project_id = Number(form.project_id);
        if (form.location) payload.location = form.location;
        if (form.notes) payload.notes = form.notes;
        await apiPost('/visits', payload);
        showSchedule.value = false;
        toast.success('Visit scheduled');
        await fetch();
    } catch (e) {
        toast.error(getApiError(e));
    } finally {
        scheduling.value = false;
    }
}

async function checkIn(id: number): Promise<void> {
    try {
        await apiPost(`/visits/${id}/check-in`);
        toast.success('Checked in');
        await fetch();
    } catch (e) {
        toast.error(getApiError(e));
    }
}

async function complete(id: number): Promise<void> {
    try {
        await apiPost(`/visits/${id}/complete`);
        toast.success('Visit completed');
        await fetch();
    } catch (e) {
        toast.error(getApiError(e));
    }
}

onMounted(async () => {
    await fetch();
    await loadSelectData();
});
</script>
