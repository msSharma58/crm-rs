<template>
    <div>
        <PageHeader title="Documents" description="Manage contracts, agreements, and files" />

        <DataTable
            :items="documents"
            :columns="columns"
            :loading="loading"
            :pagination="pagination"
            empty-title="No documents"
            @page-change="fetch"
        >
            <template #row="{ item: doc }">
                <td class="px-4 py-3 font-medium">{{ doc.title }}</td>
                <td class="px-4 py-3 capitalize text-muted-foreground">{{ doc.type.replace(/_/g, ' ') }}</td>
                <td class="px-4 py-3 text-muted-foreground">v{{ doc.version }}</td>
                <td class="px-4 py-3 text-muted-foreground">{{ doc.uploader?.name ?? '—' }}</td>
                <td class="px-4 py-3 text-muted-foreground">{{ formatDate(doc.created_at) }}</td>
            </template>
        </DataTable>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { apiGet } from '@/lib/api';
import { formatDate } from '@/lib/utils';
import type { Document, Paginated, PaginationMeta } from '@/types';
import PageHeader from '@/components/shared/PageHeader.vue';
import DataTable from '@/components/shared/DataTable.vue';
import type { TableColumn } from '@/components/ui/Table.vue';

const documents = ref<Document[]>([]);
const loading = ref(false);
const pagination = ref<PaginationMeta | null>(null);

const columns: TableColumn[] = [
    { key: 'title', label: 'Title' },
    { key: 'type', label: 'Type' },
    { key: 'version', label: 'Version' },
    { key: 'uploader', label: 'Uploaded By' },
    { key: 'created_at', label: 'Date' },
];

async function fetch(page = 1): Promise<void> {
    loading.value = true;
    try {
        const response = await apiGet<Paginated<Document>>('/documents', { params: { page, per_page: 15 } });
        documents.value = response.data;
        pagination.value = response.meta;
    } finally {
        loading.value = false;
    }
}

onMounted(() => fetch());
</script>
