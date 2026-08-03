<template>
    <div>
        <PageHeader title="Leads" description="Manage and track your sales leads">
            <template #actions>
                <Button variant="outline" @click="router.push('/leads/board')">
                    <Kanban class="h-4 w-4" />
                    Board View
                </Button>
                <Button @click="showCreate = true">
                    <Plus class="h-4 w-4" />
                    New Lead
                </Button>
            </template>
        </PageHeader>

        <div class="mb-4 flex flex-wrap gap-3">
            <Input
                v-model="search"
                placeholder="Search leads..."
                class="max-w-xs"
                @input="debouncedFetch"
            />
            <Select v-model="statusFilter" placeholder="All statuses" class="w-44" @change="fetch">
                <option value="">All statuses</option>
                <option v-for="s in LEAD_STATUSES" :key="s" :value="s">{{ LEAD_STATUS_LABELS[s] }}</option>
            </Select>
            <Select v-model="priorityFilter" placeholder="All priorities" class="w-40" @change="fetch">
                <option value="">All priorities</option>
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
            </Select>
        </div>

        <DataTable
            :items="leadsStore.leads"
            :columns="columns"
            :loading="leadsStore.loading"
            :pagination="leadsStore.pagination"
            empty-title="No leads found"
            empty-description="Create your first lead to get started"
            clickable
            @row-click="(lead) => router.push(`/leads/${lead.id}`)"
            @page-change="fetch"
        >
            <template #row="{ item: lead }">
                <td class="px-4 py-3 font-medium text-foreground">{{ lead.name }}</td>
                <td class="px-4 py-3 text-muted-foreground">{{ lead.phone ?? '—' }}</td>
                <td class="px-4 py-3"><StatusBadge :status="lead.status" type="lead" /></td>
                <td class="px-4 py-3 capitalize text-muted-foreground">{{ lead.source?.replace(/_/g, ' ') ?? '—' }}</td>
                <td class="px-4 py-3 text-muted-foreground">{{ lead.assignee?.name ?? '—' }}</td>
                <td class="px-4 py-3 text-muted-foreground">{{ formatDate(lead.created_at) }}</td>
            </template>
            <template #empty>
                <Button @click="showCreate = true"><Plus class="h-4 w-4" /> New Lead</Button>
            </template>
        </DataTable>

        <Modal :open="showCreate" title="Create Lead" @close="showCreate = false">
            <form class="space-y-4" @submit.prevent="handleCreate">
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Name *</label>
                    <Input v-model="form.name" required />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Phone</label>
                        <Input v-model="form.phone" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Email</label>
                        <Input v-model="form.email" type="email" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Source</label>
                        <Select v-model="form.source">
                            <option value="manual">Manual</option>
                            <option value="website">Website</option>
                            <option value="facebook">Facebook</option>
                            <option value="referral">Referral</option>
                            <option value="walk_in">Walk In</option>
                        </Select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Priority</label>
                        <Select v-model="form.priority">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </Select>
                    </div>
                </div>
                <div v-if="createError" class="text-sm text-red-600">{{ createError }}</div>
            </form>
            <template #footer>
                <Button variant="outline" @click="showCreate = false">Cancel</Button>
                <Button :loading="creating" @click="handleCreate">Create Lead</Button>
            </template>
        </Modal>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { Plus, Kanban } from '@lucide/vue';
import { useLeadsStore } from '@/stores/leads';
import { LEAD_STATUSES, LEAD_STATUS_LABELS } from '@/types/lead';
import { formatDate } from '@/lib/utils';
import { getApiError } from '@/lib/api';
import PageHeader from '@/components/shared/PageHeader.vue';
import DataTable from '@/components/shared/DataTable.vue';
import StatusBadge from '@/components/shared/StatusBadge.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import Select from '@/components/ui/Select.vue';
import Modal from '@/components/ui/Modal.vue';
import type { TableColumn } from '@/components/ui/Table.vue';

const router = useRouter();
const route = useRoute();
const leadsStore = useLeadsStore();

const search = ref((route.query.search as string) ?? '');
const statusFilter = ref('');
const priorityFilter = ref('');
const showCreate = ref(false);
const creating = ref(false);
const createError = ref('');

const form = reactive({
    name: '',
    phone: '',
    email: '',
    source: 'manual',
    priority: 'medium',
});

const columns: TableColumn[] = [
    { key: 'name', label: 'Name' },
    { key: 'phone', label: 'Phone' },
    { key: 'status', label: 'Status' },
    { key: 'source', label: 'Source' },
    { key: 'assignee', label: 'Assignee' },
    { key: 'created_at', label: 'Created' },
];

let debounceTimer: ReturnType<typeof setTimeout>;

function debouncedFetch(): void {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(fetch, 300);
}

async function fetch(page = 1): Promise<void> {
    leadsStore.setFilters({
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        priority: priorityFilter.value || undefined,
    });
    await leadsStore.fetchLeads(page);
}

async function handleCreate(): Promise<void> {
    creating.value = true;
    createError.value = '';
    try {
        await leadsStore.createLead(form);
        showCreate.value = false;
        Object.assign(form, { name: '', phone: '', email: '', source: 'manual', priority: 'medium' });
    } catch (e) {
        createError.value = getApiError(e);
    } finally {
        creating.value = false;
    }
}

onMounted(() => fetch());
</script>
