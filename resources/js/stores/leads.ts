import { defineStore } from 'pinia';
import { ref } from 'vue';
import { apiGet, apiPost, apiPut, apiUpload } from '@/lib/api';
import type { Customer, Lead, Paginated } from '@/types';
import type { LeadStatus } from '@/types/lead';

export interface LeadFilters {
    status?: string;
    search?: string;
    priority?: string;
    source?: string;
    assigned_to?: number;
}

export interface BoardColumn {
    status: LeadStatus;
    leads: Lead[];
}

export const useLeadsStore = defineStore('leads', () => {
    const leads = ref<Lead[]>([]);
    const board = ref<BoardColumn[]>([]);
    const currentLead = ref<Lead | null>(null);
    const pagination = ref<Paginated<Lead>['meta'] | null>(null);
    const filters = ref<LeadFilters>({});
    const loading = ref(false);
    const boardLoading = ref(false);
    const fetchError = ref<string | null>(null);

    async function fetchLeads(page = 1): Promise<void> {
        loading.value = true;
        fetchError.value = null;
        try {
            const params = { ...filters.value, page, per_page: 15 };
            const response = await apiGet<Paginated<Lead> | Lead[]>('/leads', { params });
            if (Array.isArray(response)) {
                leads.value = response;
                pagination.value = null;
            } else {
                leads.value = Array.isArray(response?.data) ? response.data : [];
                pagination.value = response?.meta ?? null;
            }
        } catch (e) {
            leads.value = [];
            pagination.value = null;
            fetchError.value = e instanceof Error ? e.message : 'Failed to load leads';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function fetchBoard(): Promise<void> {
        boardLoading.value = true;
        try {
            const response = await apiGet<BoardColumn[]>('/leads/board');
            board.value = Array.isArray(response) ? response : [];
        } catch {
            board.value = [];
        } finally {
            boardLoading.value = false;
        }
    }

    async function fetchLead(id: number): Promise<void> {
        loading.value = true;
        try {
            currentLead.value = await apiGet<Lead>(`/leads/${id}`);
        } finally {
            loading.value = false;
        }
    }

    async function createLead(data: Partial<Lead>): Promise<Lead> {
        const lead = await apiPost<Lead>('/leads', data);
        await fetchLeads();
        return lead;
    }

    async function updateLead(id: number, data: Partial<Lead>): Promise<Lead> {
        const lead = await apiPut<Lead>(`/leads/${id}`, data);
        if (currentLead.value?.id === id) {
            currentLead.value = lead;
        }
        return lead;
    }

    async function updateStatus(leadId: number, status: LeadStatus): Promise<void> {
        await apiPost<Lead>(`/leads/${leadId}/status`, { status });
        await Promise.all([fetchBoard(), fetchLeads()]);
        if (currentLead.value?.id === leadId) {
            await fetchLead(leadId);
        }
    }

    async function convertLead(id: number): Promise<Customer> {
        const customer = await apiPost<Customer>(`/leads/${id}/convert`);
        await fetchLead(id);
        return customer;
    }

    async function importCsv(file: File): Promise<unknown> {
        const formData = new FormData();
        formData.append('file', file);
        const result = await apiUpload<unknown>('/leads/import', formData);
        await fetchLeads();
        return result;
    }

    function setFilters(newFilters: LeadFilters): void {
        filters.value = { ...newFilters };
    }

    return {
        leads,
        board,
        currentLead,
        pagination,
        filters,
        loading,
        boardLoading,
        fetchError,
        fetchLeads,
        fetchBoard,
        fetchLead,
        createLead,
        updateLead,
        updateStatus,
        convertLead,
        importCsv,
        setFilters,
    };
});
