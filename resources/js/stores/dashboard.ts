import { defineStore } from 'pinia';
import { ref } from 'vue';
import { apiGet } from '@/lib/api';
import type { DashboardKpis } from '@/types';

export const useDashboardStore = defineStore('dashboard', () => {
    const kpis = ref<DashboardKpis | null>(null);
    const loading = ref(false);

    async function fetchKpis(): Promise<void> {
        loading.value = true;
        try {
            kpis.value = await apiGet<DashboardKpis>('/dashboard');
        } finally {
            loading.value = false;
        }
    }

    return { kpis, loading, fetchKpis };
});
