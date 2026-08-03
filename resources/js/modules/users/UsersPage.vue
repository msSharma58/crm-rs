<template>
    <div>
        <PageHeader title="Users" description="Manage team members and roles" />

        <DataTable
            :items="users"
            :columns="columns"
            :loading="loading"
            :pagination="pagination"
            empty-title="No users"
            @page-change="fetch"
        >
            <template #row="{ item: user }">
                <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                        <Avatar :name="user.name" :src="user.avatar_path" size="sm" />
                        <span class="font-medium">{{ user.name }}</span>
                    </div>
                </td>
                <td class="px-4 py-3 text-muted-foreground">{{ user.email }}</td>
                <td class="px-4 py-3 text-muted-foreground">{{ user.phone ?? '—' }}</td>
                <td class="px-4 py-3">
                    <Badge v-for="role in user.roles" :key="role" variant="secondary" class="mr-1">
                        {{ role }}
                    </Badge>
                </td>
                <td class="px-4 py-3">
                    <StatusBadge :status="user.is_active ? 'active' : 'cancelled'" />
                </td>
            </template>
        </DataTable>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { apiGet } from '@/lib/api';
import type { User, Paginated, PaginationMeta } from '@/types';
import PageHeader from '@/components/shared/PageHeader.vue';
import DataTable from '@/components/shared/DataTable.vue';
import StatusBadge from '@/components/shared/StatusBadge.vue';
import Avatar from '@/components/ui/Avatar.vue';
import Badge from '@/components/ui/Badge.vue';
import type { TableColumn } from '@/components/ui/Table.vue';

const users = ref<User[]>([]);
const loading = ref(false);
const pagination = ref<PaginationMeta | null>(null);

const columns: TableColumn[] = [
    { key: 'name', label: 'Name' },
    { key: 'email', label: 'Email' },
    { key: 'phone', label: 'Phone' },
    { key: 'roles', label: 'Roles' },
    { key: 'status', label: 'Status' },
];

async function fetch(page = 1): Promise<void> {
    loading.value = true;
    try {
        const response = await apiGet<Paginated<User>>('/users', { params: { page, per_page: 15 } });
        users.value = response.data;
        pagination.value = response.meta;
    } finally {
        loading.value = false;
    }
}

onMounted(() => fetch());
</script>
