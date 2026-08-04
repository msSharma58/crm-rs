<template>
    <div>
        <PageHeader title="Customers" description="Manage converted leads and customer relationships">
            <template #actions>
                <Button @click="showCreate = true">New Customer</Button>
            </template>
        </PageHeader>

        <div class="mb-4">
            <Input
                v-model="search"
                placeholder="Search customers..."
                class="max-w-xs"
                @input="debouncedFetch"
            />
        </div>

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

        <Modal :open="showCreate" title="Create Customer" @close="showCreate = false">
            <form class="space-y-4" @submit.prevent="handleCreate">
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Name *</label>
                    <Input v-model="form.name" required />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Phone *</label>
                        <Input v-model="form.phone" required />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Email</label>
                        <Input v-model="form.email" type="email" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Location</label>
                        <Input v-model="form.location" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Occupation</label>
                        <Input v-model="form.occupation" />
                    </div>
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
import { useRouter } from 'vue-router';
import { apiGet, apiPost, getApiError } from '@/lib/api';
import { formatDate } from '@/lib/utils';
import { useToast } from '@/composables/useToast';
import type { Customer, Paginated, PaginationMeta } from '@/types';
import PageHeader from '@/components/shared/PageHeader.vue';
import DataTable from '@/components/shared/DataTable.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import Modal from '@/components/ui/Modal.vue';
import type { TableColumn } from '@/components/ui/Table.vue';

const router = useRouter();
const toast = useToast();

const customers = ref<Customer[]>([]);
const loading = ref(false);
const pagination = ref<PaginationMeta | null>(null);
const search = ref('');
const showCreate = ref(false);
const creating = ref(false);
const createError = ref('');

const form = reactive({
    name: '',
    phone: '',
    email: '',
    location: '',
    occupation: '',
});

const columns: TableColumn[] = [
    { key: 'name', label: 'Name' },
    { key: 'phone', label: 'Phone' },
    { key: 'email', label: 'Email' },
    { key: 'location', label: 'Location' },
    { key: 'assignee', label: 'Assignee' },
    { key: 'created_at', label: 'Created' },
];

let debounceTimer: ReturnType<typeof setTimeout>;

function debouncedFetch(): void {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => fetch(), 300);
}

async function fetch(page = 1): Promise<void> {
    loading.value = true;
    try {
        const response = await apiGet<Paginated<Customer>>('/customers', {
            params: { page, per_page: 15, search: search.value || undefined },
        });
        customers.value = response.data;
        pagination.value = response.meta;
    } finally {
        loading.value = false;
    }
}

async function handleCreate(): Promise<void> {
    creating.value = true;
    createError.value = '';
    try {
        await apiPost('/customers', form);
        showCreate.value = false;
        toast.success('Customer created');
        Object.assign(form, { name: '', phone: '', email: '', location: '', occupation: '' });
        await fetch();
    } catch (e) {
        createError.value = getApiError(e);
    } finally {
        creating.value = false;
    }
}

onMounted(() => fetch());
</script>
