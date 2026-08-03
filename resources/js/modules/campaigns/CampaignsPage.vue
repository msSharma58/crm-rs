<template>
    <div>
        <PageHeader title="Campaigns" description="Track marketing campaigns and spend" />

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
                <td class="px-4 py-3 text-muted-foreground">{{ formatCurrency(campaign.budget) }}</td>
                <td class="px-4 py-3 text-muted-foreground">{{ formatCurrency(campaign.spend) }}</td>
                <td class="px-4 py-3"><StatusBadge :status="campaign.status" /></td>
                <td class="px-4 py-3 text-muted-foreground">{{ formatDate(campaign.starts_at) }}</td>
            </template>
        </DataTable>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { apiGet } from '@/lib/api';
import { formatCurrency, formatDate } from '@/lib/utils';
import type { Campaign, Paginated, PaginationMeta } from '@/types';
import PageHeader from '@/components/shared/PageHeader.vue';
import DataTable from '@/components/shared/DataTable.vue';
import StatusBadge from '@/components/shared/StatusBadge.vue';
import type { TableColumn } from '@/components/ui/Table.vue';

const campaigns = ref<Campaign[]>([]);
const loading = ref(false);
const pagination = ref<PaginationMeta | null>(null);

const columns: TableColumn[] = [
    { key: 'name', label: 'Name' },
    { key: 'channel', label: 'Channel' },
    { key: 'budget', label: 'Budget' },
    { key: 'spend', label: 'Spend' },
    { key: 'status', label: 'Status' },
    { key: 'starts_at', label: 'Start Date' },
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

onMounted(() => fetch());
</script>
