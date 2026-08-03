<template>
    <div>
        <PageHeader title="Follow-ups" description="Stay on top of your daily outreach">
            <template #actions>
                <Badge variant="warning">{{ overdueCount }} overdue</Badge>
                <Button @click="openCreate">New Follow-up</Button>
            </template>
        </PageHeader>

        <div class="mb-6 rounded-xl border border-brand-500/20 bg-brand-500/5 p-4">
            <h3 class="text-sm font-semibold text-brand-600 dark:text-brand-400">Today's Focus</h3>
            <p class="mt-1 text-sm text-muted-foreground">
                {{ todayCount }} follow-ups due today · {{ pendingCount }} total pending
            </p>
        </div>

        <DataTable
            :items="followUps"
            :columns="columns"
            :loading="loading"
            :pagination="pagination"
            empty-title="All caught up!"
            empty-description="No pending follow-ups"
            @page-change="fetch"
        >
            <template #row="{ item: fu }">
                <td class="px-4 py-3 font-medium">{{ fu.title }}</td>
                <td class="px-4 py-3 text-muted-foreground">{{ fu.lead?.name ?? fu.customer?.name ?? '—' }}</td>
                <td class="px-4 py-3 text-muted-foreground">{{ formatDateTime(fu.due_at) }}</td>
                <td class="px-4 py-3 capitalize text-muted-foreground">{{ fu.channel ?? '—' }}</td>
                <td class="px-4 py-3"><StatusBadge :status="fu.status" /></td>
                <td class="px-4 py-3">
                    <Button v-if="fu.status === 'pending'" size="sm" variant="outline" @click="complete(fu.id)">
                        Complete
                    </Button>
                </td>
            </template>
        </DataTable>

        <Modal :open="showCreate" title="Create Follow-up" @close="showCreate = false">
            <form class="space-y-4" @submit.prevent="handleCreate">
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Title *</label>
                    <Input v-model="form.title" required />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Channel</label>
                        <Select v-model="form.channel">
                            <option value="call">Call</option>
                            <option value="email">Email</option>
                            <option value="whatsapp">WhatsApp</option>
                            <option value="visit">Visit</option>
                        </Select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Due At *</label>
                        <Input v-model="form.due_at" type="datetime-local" required />
                    </div>
                </div>
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
import { ref, reactive, computed, onMounted } from 'vue';
import { apiGet, apiPost, getApiError } from '@/lib/api';
import { formatDateTime } from '@/lib/utils';
import { useToast } from '@/composables/useToast';
import { useAuthStore } from '@/stores/auth';
import type { FollowUp, Lead, User, Paginated, PaginationMeta } from '@/types';
import PageHeader from '@/components/shared/PageHeader.vue';
import DataTable from '@/components/shared/DataTable.vue';
import StatusBadge from '@/components/shared/StatusBadge.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import Select from '@/components/ui/Select.vue';
import Modal from '@/components/ui/Modal.vue';
import type { TableColumn } from '@/components/ui/Table.vue';

const toast = useToast();
const auth = useAuthStore();

const followUps = ref<FollowUp[]>([]);
const users = ref<User[]>([]);
const leads = ref<Lead[]>([]);
const loading = ref(false);
const pagination = ref<PaginationMeta | null>(null);
const showCreate = ref(false);
const creating = ref(false);
const createError = ref('');

const form = reactive({
    title: '',
    channel: 'call',
    due_at: '',
    assigned_to: '',
    lead_id: '',
});

const todayCount = computed(() =>
    followUps.value.filter((f) => f.due_at && new Date(f.due_at).toDateString() === new Date().toDateString()).length,
);
const pendingCount = computed(() => followUps.value.filter((f) => f.status === 'pending').length);
const overdueCount = computed(() =>
    followUps.value.filter((f) => f.status === 'pending' && f.due_at && new Date(f.due_at) < new Date()).length,
);

const columns: TableColumn[] = [
    { key: 'title', label: 'Title' },
    { key: 'contact', label: 'Contact' },
    { key: 'due_at', label: 'Due' },
    { key: 'channel', label: 'Channel' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: '' },
];

function openCreate(): void {
    form.assigned_to = auth.user ? String(auth.user.id) : '';
    showCreate.value = true;
}

async function fetch(page = 1): Promise<void> {
    loading.value = true;
    try {
        const response = await apiGet<Paginated<FollowUp>>('/follow-ups', {
            params: { page, per_page: 15, status: 'pending' },
        });
        followUps.value = response.data;
        pagination.value = response.meta;
    } finally {
        loading.value = false;
    }
}

async function loadSelectData(): Promise<void> {
    const [usersRes, leadsRes] = await Promise.all([
        apiGet<Paginated<User>>('/users', { params: { per_page: 100 } }),
        apiGet<Paginated<Lead> | Lead[]>('/leads', { params: { per_page: 100 } }),
    ]);
    users.value = usersRes.data;
    leads.value = Array.isArray(leadsRes) ? leadsRes : leadsRes.data;
}

async function handleCreate(): Promise<void> {
    creating.value = true;
    createError.value = '';
    try {
        const payload: Record<string, unknown> = {
            title: form.title,
            channel: form.channel,
            due_at: form.due_at,
            assigned_to: Number(form.assigned_to),
        };
        if (form.lead_id) payload.lead_id = Number(form.lead_id);
        await apiPost('/follow-ups', payload);
        showCreate.value = false;
        toast.success('Follow-up created');
        Object.assign(form, { title: '', channel: 'call', due_at: '', lead_id: '' });
        await fetch();
    } catch (e) {
        createError.value = getApiError(e);
    } finally {
        creating.value = false;
    }
}

async function complete(id: number): Promise<void> {
    try {
        await apiPost(`/follow-ups/${id}/complete`);
        toast.success('Follow-up completed');
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
