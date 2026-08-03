<template>
    <div>
        <PageHeader title="Customers" description="Manage converted leads and customer relationships" />

        <DataTable
            :items="customers"
            :columns="columns"
            :loading="loading"
            :pagination="pagination"
            empty-title="No customers yet"
            clickable
            @row-click="(c) => router.push(`/customers/${c.id}`)"
            @page-change="fetch"
        >
            <template #row="{ item: customer }">
                <td class="px-4 py-3 font-medium text-foreground">{{ customer.name }}</td>
                <td class="px-4 py-3 text-muted-foreground">{{ customer.phone ?? '—' }}</td>
                <td class="px-4 py-3 text-muted-foreground">{{ customer.email ?? '—' }}</td>
                <td class="px-4 py-3 text-muted-foreground">{{ customer.location ?? '—' }}</td>
                <td class="px-4 py-3 text-muted-foreground">{{ customer.assignee?.name ?? '—' }}</td>
                <td class="px-4 py-3 text-muted-foreground">{{ formatDate(customer.created_at) }}</td>
            </template>
        </DataTable>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { apiGet } from '@/lib/api';
import { formatDate } from '@/lib/utils';
import type { Customer, Paginated, PaginationMeta } from '@/types';
import PageHeader from '@/components/shared/PageHeader.vue';
import DataTable from '@/components/shared/DataTable.vue';
import type { TableColumn } from '@/components/ui/Table.vue';

const router = useRouter();
const customers = ref<Customer[]>([]);
const loading = ref(false);
const pagination = ref<PaginationMeta | null>(null);

const columns: TableColumn[] = [
    { key: 'name', label: 'Name' },
    { key: 'phone', label: 'Phone' },
    { key: 'email', label: 'Email' },
    { key: 'location', label: 'Location' },
    { key: 'assignee', label: 'Assignee' },
    { key: 'created_at', label: 'Created' },
];

async function fetch(page = 1): Promise<void> {
    loading.value = true;
    try {
        const response = await apiGet<Paginated<Customer>>('/customers', { params: { page, per_page: 15 } });
        customers.value = response.data;
        pagination.value = response.meta;
    } finally {
        loading.value = false;
    }
}

onMounted(() => fetch());
</script>
