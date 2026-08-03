<template>
    <div>
        <PageHeader title="Projects" description="Manage property projects and inventory" />

        <DataTable
            :items="projects"
            :columns="columns"
            :loading="loading"
            :pagination="pagination"
            empty-title="No projects yet"
            clickable
            @row-click="(p) => router.push(`/projects/${p.id}`)"
            @page-change="fetch"
        >
            <template #row="{ item: project }">
                <td class="px-4 py-3 font-medium text-foreground">{{ project.name }}</td>
                <td class="px-4 py-3 text-muted-foreground">{{ project.code ?? '—' }}</td>
                <td class="px-4 py-3 text-muted-foreground">{{ project.location ?? '—' }}</td>
                <td class="px-4 py-3"><StatusBadge :status="project.status" /></td>
                <td class="px-4 py-3 text-muted-foreground">{{ project.units_count ?? '—' }}</td>
            </template>
        </DataTable>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { apiGet } from '@/lib/api';
import type { Project, Paginated, PaginationMeta } from '@/types';
import PageHeader from '@/components/shared/PageHeader.vue';
import DataTable from '@/components/shared/DataTable.vue';
import StatusBadge from '@/components/shared/StatusBadge.vue';
import type { TableColumn } from '@/components/ui/Table.vue';

const router = useRouter();
const projects = ref<Project[]>([]);
const loading = ref(false);
const pagination = ref<PaginationMeta | null>(null);

const columns: TableColumn[] = [
    { key: 'name', label: 'Name' },
    { key: 'code', label: 'Code' },
    { key: 'location', label: 'Location' },
    { key: 'status', label: 'Status' },
    { key: 'units', label: 'Units' },
];

async function fetch(page = 1): Promise<void> {
    loading.value = true;
    try {
        const response = await apiGet<Paginated<Project>>('/projects', { params: { page, per_page: 15 } });
        projects.value = response.data;
        pagination.value = response.meta;
    } finally {
        loading.value = false;
    }
}

onMounted(() => fetch());
</script>
