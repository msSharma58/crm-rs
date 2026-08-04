<template>
    <div>
        <PageHeader title="Campaigns" description="Track marketing campaigns and spend">
            <template #actions>
                <Button @click="showCreate = true">New Campaign</Button>
            </template>
        </PageHeader>

        <DataTable
            :items="campaigns"
            :columns="columns"
            :loading="loading"
            :pagination="pagination"
            empty-title="No campaigns"
            @page-change="fetch"
        >
            <template #row="{ item: campaign }">
                <td class="px-4 py-3 font-medium">{{ campaign.name }}</td>
                <td class="px-4 py-3 capitalize text-muted-foreground">{{ campaign.channel ?? '—' }}</td>
                <td class="px-4 py-3 text-muted-foreground">{{ campaign.leads_count ?? 0 }}</td>
                <td class="px-4 py-3 text-muted-foreground">{{ formatCurrency(campaign.cost_per_lead) }}</td>
                <td class="px-4 py-3 text-muted-foreground">{{ campaign.conversion_rate ?? 0 }}%</td>
                <td class="px-4 py-3 text-muted-foreground">{{ campaign.roi != null ? `${campaign.roi}%` : '—' }}</td>
                <td class="px-4 py-3 text-muted-foreground">{{ formatCurrency(campaign.budget) }}</td>
                <td class="px-4 py-3 text-muted-foreground">{{ formatCurrency(campaign.spend) }}</td>
                <td class="px-4 py-3"><StatusBadge :status="campaign.status" /></td>
            </template>
        </DataTable>

        <Modal :open="showCreate" title="Create Campaign" @close="showCreate = false">
            <form class="space-y-4" @submit.prevent="handleCreate">
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Name *</label>
                    <Input v-model="form.name" required />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Channel *</label>
                        <Select v-model="form.channel">
                            <option value="facebook">Facebook</option>
                            <option value="google">Google</option>
                            <option value="instagram">Instagram</option>
                            <option value="referral">Referral</option>
                            <option value="other">Other</option>
                        </Select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Budget</label>
                        <Input v-model="form.budget" type="number" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Start Date</label>
                        <Input v-model="form.starts_at" type="date" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">End Date</label>
                        <Input v-model="form.ends_at" type="date" />
                    </div>
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
import { formatCurrency } from '@/lib/utils';
import { useToast } from '@/composables/useToast';
import type { Campaign, Paginated, PaginationMeta } from '@/types';
import PageHeader from '@/components/shared/PageHeader.vue';
import DataTable from '@/components/shared/DataTable.vue';
import StatusBadge from '@/components/shared/StatusBadge.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import Select from '@/components/ui/Select.vue';
import Modal from '@/components/ui/Modal.vue';
import type { TableColumn } from '@/components/ui/Table.vue';

const toast = useToast();

const campaigns = ref<Campaign[]>([]);
const loading = ref(false);
const pagination = ref<PaginationMeta | null>(null);
const showCreate = ref(false);
const creating = ref(false);
const createError = ref('');

const form = reactive({
    name: '',
    channel: 'facebook',
    budget: '',
    starts_at: '',
    ends_at: '',
});

const columns: TableColumn[] = [
    { key: 'name', label: 'Name' },
    { key: 'channel', label: 'Channel' },
    { key: 'leads', label: 'Leads' },
    { key: 'cpl', label: 'CPL' },
    { key: 'conversion', label: 'Conversion' },
    { key: 'roi', label: 'ROI' },
    { key: 'budget', label: 'Budget' },
    { key: 'spend', label: 'Spend' },
    { key: 'status', label: 'Status' },
];

async function fetch(page = 1): Promise<void> {
    loading.value = true;
    try {
        const response = await apiGet<Paginated<Campaign>>('/campaigns', { params: { page, per_page: 15 } });
        campaigns.value = response.data;
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
            name: form.name,
            channel: form.channel,
            status: 'active',
        };
        if (form.budget) payload.budget = Number(form.budget);
        if (form.starts_at) payload.starts_at = form.starts_at;
        if (form.ends_at) payload.ends_at = form.ends_at;
        await apiPost('/campaigns', payload);
        showCreate.value = false;
        toast.success('Campaign created');
        Object.assign(form, { name: '', channel: 'facebook', budget: '', starts_at: '', ends_at: '' });
        await fetch();
    } catch (e) {
        createError.value = getApiError(e);
    } finally {
        creating.value = false;
    }
}

onMounted(() => fetch());
</script>
