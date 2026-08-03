<template>
    <div>
        <PageHeader title="Bookings" description="Manage unit reservations and bookings" />

        <DataTable
            :items="bookings"
            :columns="columns"
            :loading="loading"
            :pagination="pagination"
            empty-title="No bookings"
            @page-change="fetch"
        >
            <template #row="{ item: booking }">
                <td class="px-4 py-3 font-medium">{{ booking.code }}</td>
                <td class="px-4 py-3 text-muted-foreground">{{ booking.customer?.name ?? '—' }}</td>
                <td class="px-4 py-3 text-muted-foreground">{{ booking.unit?.code ?? '—' }}</td>
                <td class="px-4 py-3">{{ formatCurrency(booking.total_amount) }}</td>
                <td class="px-4 py-3"><StatusBadge :status="booking.status" /></td>
                <td class="px-4 py-3 text-muted-foreground">{{ formatDate(booking.booked_at) }}</td>
            </template>
        </DataTable>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { apiGet } from '@/lib/api';
import { formatCurrency, formatDate } from '@/lib/utils';
import type { Booking, Paginated, PaginationMeta } from '@/types';
import PageHeader from '@/components/shared/PageHeader.vue';
import DataTable from '@/components/shared/DataTable.vue';
import StatusBadge from '@/components/shared/StatusBadge.vue';
import type { TableColumn } from '@/components/ui/Table.vue';

const bookings = ref<Booking[]>([]);
const loading = ref(false);
const pagination = ref<PaginationMeta | null>(null);

const columns: TableColumn[] = [
    { key: 'code', label: 'Code' },
    { key: 'customer', label: 'Customer' },
    { key: 'unit', label: 'Unit' },
    { key: 'total', label: 'Total' },
    { key: 'status', label: 'Status' },
    { key: 'booked_at', label: 'Booked' },
];

async function fetch(page = 1): Promise<void> {
    loading.value = true;
    try {
        const response = await apiGet<Paginated<Booking>>('/bookings', { params: { page, per_page: 15 } });
        bookings.value = response.data;
        pagination.value = response.meta;
    } finally {
        loading.value = false;
    }
}

onMounted(() => fetch());
</script>
