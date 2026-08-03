<template>
    <div>
        <PageHeader title="Payments" description="Track collections and outstanding amounts">
            <template #actions>
                <div class="rounded-lg border border-amber-500/30 bg-amber-500/10 px-4 py-2">
                    <p class="text-xs text-amber-600 dark:text-amber-400">Outstanding</p>
                    <p class="text-lg font-semibold text-amber-700 dark:text-amber-300">{{ formatCurrency(outstanding) }}</p>
                </div>
                <Button @click="showRecord = true">Record Payment</Button>
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
                <td class="px-4 py-3">
                    <Button
                        v-if="payment.status === 'completed'"
                        size="sm"
                        variant="ghost"
                        class="text-red-600"
                        @click="refund(payment)"
                    >
                        Refund
                    </Button>
                </td>
            </template>
        </DataTable>

        <Modal :open="showRecord" title="Record Payment" @close="showRecord = false">
            <form class="space-y-4" @submit.prevent="handleRecord">
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Booking ID *</label>
                    <Input v-model="recordForm.booking_id" type="number" required />
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Customer ID *</label>
                    <Input v-model="recordForm.customer_id" type="number" required />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Amount *</label>
                        <Input v-model="recordForm.amount" type="number" required />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Method</label>
                        <Select v-model="recordForm.method">
                            <option value="cash">Cash</option>
                            <option value="bank">Bank</option>
                            <option value="cheque">Cheque</option>
                            <option value="online">Online</option>
                        </Select>
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Receipt No</label>
                    <Input v-model="recordForm.receipt_no" />
                </div>
                <div v-if="recordError" class="text-sm text-red-600">{{ recordError }}</div>
            </form>
            <template #footer>
                <Button variant="outline" @click="showRecord = false">Cancel</Button>
                <Button :loading="recording" @click="handleRecord">Record</Button>
            </template>
        </Modal>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { apiGet, apiPost, getApiError } from '@/lib/api';
import { formatCurrency, formatDate } from '@/lib/utils';
import { useToast } from '@/composables/useToast';
import type { Payment, OutstandingPayments, Paginated, PaginationMeta } from '@/types';
import PageHeader from '@/components/shared/PageHeader.vue';
import DataTable from '@/components/shared/DataTable.vue';
import StatusBadge from '@/components/shared/StatusBadge.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import Select from '@/components/ui/Select.vue';
import Modal from '@/components/ui/Modal.vue';
import type { TableColumn } from '@/components/ui/Table.vue';

const toast = useToast();

const payments = ref<Payment[]>([]);
const outstanding = ref(0);
const loading = ref(false);
const pagination = ref<PaginationMeta | null>(null);
const showRecord = ref(false);
const recording = ref(false);
const recordError = ref('');

const recordForm = reactive({
    booking_id: '',
    customer_id: '',
    amount: '',
    method: 'cash',
    receipt_no: '',
});

const columns: TableColumn[] = [
    { key: 'receipt', label: 'Receipt' },
    { key: 'customer', label: 'Customer' },
    { key: 'amount', label: 'Amount' },
    { key: 'method', label: 'Method' },
    { key: 'status', label: 'Status' },
    { key: 'paid_at', label: 'Date' },
    { key: 'actions', label: '' },
];

async function fetchOutstanding(): Promise<void> {
    try {
        const data = await apiGet<OutstandingPayments>('/payments/outstanding');
        outstanding.value = data.total_outstanding;
    } catch {
        outstanding.value = 0;
    }
}

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

async function handleRecord(): Promise<void> {
    recording.value = true;
    recordError.value = '';
    try {
        const payload: Record<string, unknown> = {
            booking_id: Number(recordForm.booking_id),
            customer_id: Number(recordForm.customer_id),
            amount: Number(recordForm.amount),
            method: recordForm.method,
            status: 'completed',
        };
        if (recordForm.receipt_no) payload.receipt_no = recordForm.receipt_no;
        await apiPost('/payments', payload);
        showRecord.value = false;
        toast.success('Payment recorded');
        Object.assign(recordForm, { booking_id: '', customer_id: '', amount: '', method: 'cash', receipt_no: '' });
        await fetch();
        await fetchOutstanding();
    } catch (e) {
        recordError.value = getApiError(e);
    } finally {
        recording.value = false;
    }
}

async function refund(payment: Payment): Promise<void> {
    try {
        await apiPost(`/payments/${payment.id}/refund`, { amount: payment.amount });
        toast.success('Refund processed');
        await fetch();
        await fetchOutstanding();
    } catch (e) {
        toast.error(getApiError(e));
    }
}

onMounted(async () => {
    await fetch();
    await fetchOutstanding();
});
</script>
