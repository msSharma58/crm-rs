<template>
    <div>
        <div v-if="loading" class="flex justify-center py-20">
            <LoadingSpinner class="h-8 w-8" />
        </div>
        <template v-else-if="project">
            <button class="mb-4 flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground" @click="router.back()">
                <ArrowLeft class="h-4 w-4" /> Back
            </button>
            <PageHeader :title="project.name" :description="project.location ?? ''">
                <template #actions>
                    <StatusBadge :status="project.status" />
                </template>
            </PageHeader>

            <p v-if="project.description" class="mb-6 text-sm text-muted-foreground">{{ project.description }}</p>

            <h3 class="mb-4 text-base font-semibold text-foreground">Units</h3>
            <DataTable
                :items="project.units ?? []"
                :columns="unitColumns"
                :loading="false"
                empty-title="No units"
            >
                <template #row="{ item: unit }">
                    <td class="px-4 py-3 font-medium">{{ unit.code }}</td>
                    <td class="px-4 py-3 text-muted-foreground">{{ unit.type ?? '—' }}</td>
                    <td class="px-4 py-3 text-muted-foreground">{{ unit.area_sqft ? `${unit.area_sqft} sqft` : '—' }}</td>
                    <td class="px-4 py-3">{{ formatCurrency(unit.price) }}</td>
                    <td class="px-4 py-3"><StatusBadge :status="unit.status" /></td>
                </template>
            </DataTable>
        </template>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { ArrowLeft } from 'lucide-vue-next';
import { apiGet } from '@/lib/api';
import { formatCurrency } from '@/lib/utils';
import type { Project } from '@/types';
import PageHeader from '@/components/shared/PageHeader.vue';
import DataTable from '@/components/shared/DataTable.vue';
import StatusBadge from '@/components/shared/StatusBadge.vue';
import LoadingSpinner from '@/components/shared/LoadingSpinner.vue';
import type { TableColumn } from '@/components/ui/Table.vue';

const route = useRoute();
const router = useRouter();
const project = ref<Project | null>(null);
const loading = ref(false);

const unitColumns: TableColumn[] = [
    { key: 'code', label: 'Unit' },
    { key: 'type', label: 'Type' },
    { key: 'area', label: 'Area' },
    { key: 'price', label: 'Price' },
    { key: 'status', label: 'Availability' },
];

onMounted(async () => {
    loading.value = true;
    try {
        project.value = await apiGet<Project>(`/projects/${route.params.id}`);
    } finally {
        loading.value = false;
    }
});
</script>
