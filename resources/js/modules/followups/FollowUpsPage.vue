<template>
    <div>
        <PageHeader title="Follow-ups" description="Stay on top of your daily outreach">
            <template #actions>
                <Badge variant="warning">{{ overdueCount }} overdue</Badge>
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
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { apiGet, apiPost } from '@/lib/api';
import { formatDateTime } from '@/lib/utils';
import type { FollowUp, Paginated, PaginationMeta } from '@/types';
import PageHeader from '@/components/shared/PageHeader.vue';
import DataTable from '@/components/shared/DataTable.vue';
import StatusBadge from '@/components/shared/StatusBadge.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import type { TableColumn } from '@/components/ui/Table.vue';

const followUps = ref<FollowUp[]>([]);
const loading = ref(false);
const pagination = ref<PaginationMeta | null>(null);

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

async function complete(id: number): Promise<void> {
    await apiPost(`/follow-ups/${id}/complete`);
    await fetch();
}

onMounted(() => fetch());
</script>
