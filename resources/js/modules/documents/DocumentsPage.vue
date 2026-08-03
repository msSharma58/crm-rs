<template>
    <div>
        <PageHeader title="Documents" description="Manage contracts, agreements, and files">
            <template #actions>
                <Button @click="showUpload = true">Upload Document</Button>
            </template>
        </PageHeader>

        <div class="mb-4">
            <Select v-model="typeFilter" placeholder="All types" class="w-48" @change="fetch">
                <option value="">All types</option>
                <option value="contract">Contract</option>
                <option value="agreement">Agreement</option>
                <option value="identity">Identity</option>
                <option value="other">Other</option>
            </Select>
        </div>

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
                <td class="px-4 py-3">
                    <Button size="sm" variant="ghost" class="text-red-600" @click="handleDelete(doc.id)">
                        Delete
                    </Button>
                </td>
            </template>
        </DataTable>

        <Modal :open="showUpload" title="Upload Document" @close="showUpload = false">
            <form class="space-y-4" @submit.prevent="handleUpload">
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Title *</label>
                    <Input v-model="uploadForm.title" required />
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Type *</label>
                    <Select v-model="uploadForm.type">
                        <option value="contract">Contract</option>
                        <option value="agreement">Agreement</option>
                        <option value="identity">Identity</option>
                        <option value="other">Other</option>
                    </Select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Customer ID (optional)</label>
                    <Input v-model="uploadForm.customer_id" type="number" placeholder="Link to customer" />
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium">File *</label>
                    <input
                        ref="fileInput"
                        type="file"
                        class="block w-full text-sm text-muted-foreground file:mr-4 file:rounded-lg file:border-0 file:bg-brand-500 file:px-4 file:py-2 file:text-sm file:text-white"
                        required
                        @change="onFileSelect"
                    />
                </div>
                <div v-if="uploadError" class="text-sm text-red-600">{{ uploadError }}</div>
            </form>
            <template #footer>
                <Button variant="outline" @click="showUpload = false">Cancel</Button>
                <Button :loading="uploading" @click="handleUpload">Upload</Button>
            </template>
        </Modal>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { apiGet, apiDelete, apiUpload, getApiError } from '@/lib/api';
import { formatDate } from '@/lib/utils';
import { useToast } from '@/composables/useToast';
import type { Document, Paginated, PaginationMeta } from '@/types';
import PageHeader from '@/components/shared/PageHeader.vue';
import DataTable from '@/components/shared/DataTable.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import Select from '@/components/ui/Select.vue';
import Modal from '@/components/ui/Modal.vue';
import type { TableColumn } from '@/components/ui/Table.vue';

const toast = useToast();

const documents = ref<Document[]>([]);
const loading = ref(false);
const pagination = ref<PaginationMeta | null>(null);
const typeFilter = ref('');
const showUpload = ref(false);
const uploading = ref(false);
const uploadError = ref('');
const fileInput = ref<HTMLInputElement | null>(null);
const selectedFile = ref<File | null>(null);

const uploadForm = reactive({
    title: '',
    type: 'contract',
    customer_id: '',
});

const columns: TableColumn[] = [
    { key: 'title', label: 'Title' },
    { key: 'type', label: 'Type' },
    { key: 'version', label: 'Version' },
    { key: 'uploader', label: 'Uploaded By' },
    { key: 'created_at', label: 'Date' },
    { key: 'actions', label: '' },
];

function onFileSelect(event: Event): void {
    const input = event.target as HTMLInputElement;
    selectedFile.value = input.files?.[0] ?? null;
}

async function fetch(page = 1): Promise<void> {
    loading.value = true;
    try {
        const response = await apiGet<Paginated<Document>>('/documents', {
            params: { page, per_page: 15, type: typeFilter.value || undefined },
        });
        documents.value = response.data;
        pagination.value = response.meta;
    } finally {
        loading.value = false;
    }
}

async function handleUpload(): Promise<void> {
    if (!selectedFile.value) {
        uploadError.value = 'Please select a file';
        return;
    }
    uploading.value = true;
    uploadError.value = '';
    try {
        const formData = new FormData();
        formData.append('title', uploadForm.title);
        formData.append('type', uploadForm.type);
        formData.append('file', selectedFile.value);
        if (uploadForm.customer_id) {
            formData.append('documentable_type', 'customer');
            formData.append('documentable_id', uploadForm.customer_id);
        }
        await apiUpload('/documents', formData);
        showUpload.value = false;
        toast.success('Document uploaded');
        Object.assign(uploadForm, { title: '', type: 'contract', customer_id: '' });
        selectedFile.value = null;
        if (fileInput.value) fileInput.value.value = '';
        await fetch();
    } catch (e) {
        uploadError.value = getApiError(e);
    } finally {
        uploading.value = false;
    }
}

async function handleDelete(id: number): Promise<void> {
    try {
        await apiDelete(`/documents/${id}`);
        toast.success('Document deleted');
        await fetch();
    } catch (e) {
        toast.error(getApiError(e));
    }
}

onMounted(() => fetch());
</script>
