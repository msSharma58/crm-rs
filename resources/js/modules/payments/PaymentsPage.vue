<template>
    <div>
        <PageHeader title="Payments" description="Track collections and outstanding amounts">
            <template #actions>
                <div class="rounded-lg border border-amber-500/30 bg-amber-500/10 px-4 py-2">
                    <p class="text-xs text-amber-600 dark:text-amber-400">Outstanding</p>
                    <p class="text-lg font-semibold text-amber-700 dark:text-amber-300">{{ formatCurrency(outstanding) }}</p>
                </div>
            </template>
        </PageHeader>

        <DataTable
            :items="payments"
            :columns="columns"
            :loading="loading"
            :pagination="pagination"
            empty-title="No payments"
            @page-change="fetch"
        >
            <template #row="{ item: payment }">
                <td class="px-4 py-3 font-medium">{{ payment.receipt_no ?? `#${payment.id}` }}</td>
                <td class="px-4 py-3 text-muted-foreground">{{ payment.customer?.name ?? '—' }}</td>
                <td class="px-4 py-3 font-medium" :class="payment.status === 'pending' ? 'text-amber-600' : ''">
                    {{ formatCurrency(payment.amount) }}
                </td>
                <td class="px-4 py-3 capitalize text-muted-foreground">{{ payment.method ?? '—' }}</td>
                <td class="px-4 py-3"><StatusBadge :status="payment.status" /></td>
                <td class="px-4 py-3 text-muted-foreground">{{ formatDate(payment.paid_at) }}</td>
            </template>
        </DataTable>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { apiGet } from '@/lib/api';
import { formatCurrency, formatDate } from '@/lib/utils';
import type { Payment, Paginated, PaginationMeta } from '@/types';
import PageHeader from '@/components/shared/PageHeader.vue';
import DataTable from '@/components/shared/DataTable.vue';
import StatusBadge from '@/components/shared/StatusBadge.vue';
import type { TableColumn } from '@/components/ui/Table.vue';

const payments = ref<Payment[]>([]);
const loading = ref(false);
const pagination = ref<PaginationMeta | null>(null);

const outstanding = computed(() =>
    payments.value.filter((p) => p.status === 'pending').reduce((sum, p) => sum + p.amount, 0),
);

const columns: TableColumn[] = [
    { key: 'receipt', label: 'Receipt' },
    { key: 'customer', label: 'Customer' },
    { key: 'amount', label: 'Amount' },
    { key: 'method', label: 'Method' },
    { key: 'status', label: 'Status' },
    { key: 'paid_at', label: 'Date' },
];

async function fetch(page = 1): Promise<void> {
    loading.value = true;
    try {
        const response = await apiGet<Paginated<Payment>>('/payments', { params: { page, per_page: 15 } });
        payments.value = response.data;
        pagination.value = response.meta;
    } finally {
        loading.value = false;
    }
}

onMounted(() => fetch());
</script>
