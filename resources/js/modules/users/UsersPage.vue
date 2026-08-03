<template>
    <div>
        <PageHeader title="Users" description="Manage team members and roles">
            <template #actions>
                <Button @click="showCreate = true">New User</Button>
            </template>
        </PageHeader>

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

        <Modal :open="showCreate" title="Create User" @close="showCreate = false">
            <form class="space-y-4" @submit.prevent="handleCreate">
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Name *</label>
                    <Input v-model="form.name" required />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Email *</label>
                        <Input v-model="form.email" type="email" required />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Phone</label>
                        <Input v-model="form.phone" />
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Password *</label>
                    <Input v-model="form.password" type="password" required minlength="8" />
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Role</label>
                    <Select v-model="form.role">
                        <option value="">Default</option>
                        <option value="admin">Admin</option>
                        <option value="sales_executive">Sales Executive</option>
                        <option value="manager">Manager</option>
                    </Select>
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
import { useToast } from '@/composables/useToast';
import type { User, Paginated, PaginationMeta } from '@/types';
import PageHeader from '@/components/shared/PageHeader.vue';
import DataTable from '@/components/shared/DataTable.vue';
import StatusBadge from '@/components/shared/StatusBadge.vue';
import Avatar from '@/components/ui/Avatar.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import Select from '@/components/ui/Select.vue';
import Modal from '@/components/ui/Modal.vue';
import type { TableColumn } from '@/components/ui/Table.vue';

const toast = useToast();

const users = ref<User[]>([]);
const loading = ref(false);
const pagination = ref<PaginationMeta | null>(null);
const showCreate = ref(false);
const creating = ref(false);
const createError = ref('');

const form = reactive({
    name: '',
    email: '',
    phone: '',
    password: '',
    role: '',
});

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

async function handleCreate(): Promise<void> {
    creating.value = true;
    createError.value = '';
    try {
        const payload: Record<string, unknown> = {
            name: form.name,
            email: form.email,
            password: form.password,
        };
        if (form.phone) payload.phone = form.phone;
        if (form.role) payload.roles = [form.role];
        await apiPost('/users', payload);
        showCreate.value = false;
        toast.success('User created');
        Object.assign(form, { name: '', email: '', phone: '', password: '', role: '' });
        await fetch();
    } catch (e) {
        createError.value = getApiError(e);
    } finally {
        creating.value = false;
    }
}

onMounted(() => fetch());
</script>
