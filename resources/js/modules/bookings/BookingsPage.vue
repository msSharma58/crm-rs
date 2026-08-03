<template>
    <div>
        <PageHeader title="Bookings" description="Manage unit reservations and bookings">
            <template #actions>
                <Button @click="openCreate">New Booking</Button>
            </template>
        </PageHeader>

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
                <td class="px-4 py-3">
                    <div class="flex gap-1">
                        <Button
                            v-if="booking.status === 'draft'"
                            size="sm"
                            variant="outline"
                            @click="reserve(booking.id)"
                        >
                            Reserve
                        </Button>
                        <Button
                            v-if="booking.status === 'reserved'"
                            size="sm"
                            variant="outline"
                            @click="confirm(booking.id)"
                        >
                            Confirm
                        </Button>
                        <Button
                            v-if="booking.status !== 'cancelled' && booking.status !== 'sold'"
                            size="sm"
                            variant="ghost"
                            class="text-red-600"
                            @click="cancel(booking.id)"
                        >
                            Cancel
                        </Button>
                    </div>
                </td>
            </template>
        </DataTable>

        <Modal :open="showCreate" title="Create Booking" size="lg" @close="showCreate = false">
            <form class="space-y-4" @submit.prevent="handleCreate">
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Customer *</label>
                    <Select v-model="form.customer_id" required>
                        <option value="" disabled>Select customer</option>
                        <option v-for="c in customers" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
                    </Select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Project *</label>
                        <Select v-model="form.project_id" required @change="loadUnits">
                            <option value="" disabled>Select project</option>
                            <option v-for="p in projects" :key="p.id" :value="String(p.id)">{{ p.name }}</option>
                        </Select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Unit *</label>
                        <Select v-model="form.unit_id" required>
                            <option value="" disabled>Select unit</option>
                            <option v-for="u in units" :key="u.id" :value="String(u.id)">{{ u.code }} — {{ formatCurrency(u.price) }}</option>
                        </Select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Booking Amount</label>
                        <Input v-model="form.booking_amount" type="number" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Total Amount *</label>
                        <Input v-model="form.total_amount" type="number" required />
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
import { apiGet, apiPost, getApiError } from '@/lib/api';
import { formatCurrency, formatDate } from '@/lib/utils';
import { useToast } from '@/composables/useToast';
import type { Booking, Customer, Project, Unit, Paginated, PaginationMeta } from '@/types';
import PageHeader from '@/components/shared/PageHeader.vue';
import DataTable from '@/components/shared/DataTable.vue';
import StatusBadge from '@/components/shared/StatusBadge.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import Select from '@/components/ui/Select.vue';
import Modal from '@/components/ui/Modal.vue';
import type { TableColumn } from '@/components/ui/Table.vue';

const toast = useToast();

const bookings = ref<Booking[]>([]);
const customers = ref<Customer[]>([]);
const projects = ref<Project[]>([]);
const units = ref<Unit[]>([]);
const loading = ref(false);
const pagination = ref<PaginationMeta | null>(null);
const showCreate = ref(false);
const creating = ref(false);
const createError = ref('');

const form = reactive({
    customer_id: '',
    project_id: '',
    unit_id: '',
    booking_amount: '',
    total_amount: '',
});

const columns: TableColumn[] = [
    { key: 'code', label: 'Code' },
    { key: 'customer', label: 'Customer' },
    { key: 'unit', label: 'Unit' },
    { key: 'total', label: 'Total' },
    { key: 'status', label: 'Status' },
    { key: 'booked_at', label: 'Booked' },
    { key: 'actions', label: '' },
];

function openCreate(): void {
    showCreate.value = true;
}

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

async function loadSelectData(): Promise<void> {
    const [customersRes, projectsRes] = await Promise.all([
        apiGet<Paginated<Customer>>('/customers', { params: { per_page: 100 } }),
        apiGet<Paginated<Project>>('/projects', { params: { per_page: 100 } }),
    ]);
    customers.value = customersRes.data;
    projects.value = projectsRes.data;
}

async function loadUnits(): Promise<void> {
    if (!form.project_id) {
        units.value = [];
        return;
    }
    const project = await apiGet<Project>(`/projects/${form.project_id}`);
    units.value = project.units ?? [];
    form.unit_id = '';
}

async function handleCreate(): Promise<void> {
    creating.value = true;
    createError.value = '';
    try {
        const payload: Record<string, unknown> = {
            customer_id: Number(form.customer_id),
            project_id: Number(form.project_id),
            unit_id: Number(form.unit_id),
            total_amount: Number(form.total_amount),
        };
        if (form.booking_amount) payload.booking_amount = Number(form.booking_amount);
        await apiPost('/bookings', payload);
        showCreate.value = false;
        toast.success('Booking created');
        Object.assign(form, { customer_id: '', project_id: '', unit_id: '', booking_amount: '', total_amount: '' });
        await fetch();
    } catch (e) {
        createError.value = getApiError(e);
    } finally {
        creating.value = false;
    }
}

async function reserve(id: number): Promise<void> {
    try {
        await apiPost(`/bookings/${id}/reserve`);
        toast.success('Unit reserved');
        await fetch();
    } catch (e) {
        toast.error(getApiError(e));
    }
}

async function confirm(id: number): Promise<void> {
    try {
        await apiPost(`/bookings/${id}/confirm`);
        toast.success('Booking confirmed');
        await fetch();
    } catch (e) {
        toast.error(getApiError(e));
    }
}

async function cancel(id: number): Promise<void> {
    try {
        await apiPost(`/bookings/${id}/cancel`);
        toast.success('Booking cancelled');
        await fetch();
    } catch (e) {
        toast.error(getApiError(e));
    }
}

onMounted(async () => {
    await fetch();
    await loadSelectData();
});
</script>
