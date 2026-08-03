<template>
    <div>
        <PageHeader title="Reports" description="Analytics and exportable reports">
            <template #actions>
                <Button variant="outline" :loading="exportingLeads" @click="exportReport('leads')">
                    <Download class="h-4 w-4" />
                    Export Leads
                </Button>
                <Button variant="outline" :loading="exportingSales" @click="exportReport('sales')">
                    <Download class="h-4 w-4" />
                    Export Sales
                </Button>
            </template>
        </PageHeader>

        <div class="grid gap-6 lg:grid-cols-2">
            <Card>
                <template #header>
                    <h3 class="text-base font-semibold">Leads by Status</h3>
                </template>
                <div v-if="leadsReportLoading" class="flex h-48 items-center justify-center">
                    <LoadingSpinner class="h-6 w-6" />
                </div>
                <div v-else class="h-64">
                    <Bar v-if="chartData" :data="chartData" :options="chartOptions" />
                    <p v-else class="py-16 text-center text-sm text-muted-foreground">No lead data available</p>
                </div>
            </Card>

            <Card>
                <template #header>
                    <h3 class="text-base font-semibold">Report Summary</h3>
                </template>
                <div v-if="loading" class="flex justify-center py-8">
                    <LoadingSpinner class="h-6 w-6" />
                </div>
                <div v-else-if="overview" class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-lg bg-muted p-4 text-center">
                        <p class="text-2xl font-semibold">{{ overview.total_leads }}</p>
                        <p class="text-xs text-muted-foreground">Total Leads</p>
                    </div>
                    <div class="rounded-lg bg-muted p-4 text-center">
                        <p class="text-2xl font-semibold">{{ overview.total_bookings }}</p>
                        <p class="text-xs text-muted-foreground">Bookings</p>
                    </div>
                    <div class="rounded-lg bg-muted p-4 text-center">
                        <p class="text-2xl font-semibold">{{ formatCurrency(overview.total_revenue) }}</p>
                        <p class="text-xs text-muted-foreground">Revenue</p>
                    </div>
                    <div class="rounded-lg bg-muted p-4 text-center">
                        <p class="text-2xl font-semibold">{{ overview.conversion_rate }}%</p>
                        <p class="text-xs text-muted-foreground">Conversion</p>
                    </div>
                </div>
            </Card>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Download } from '@lucide/vue';
import { Bar } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend,
} from 'chart.js';
import { apiGet, downloadAuthenticated, getApiError } from '@/lib/api';
import { formatCurrency } from '@/lib/utils';
import { useToast } from '@/composables/useToast';
import type { ReportsOverview, LeadsReport } from '@/types';
import { LEAD_STATUS_LABELS, type LeadStatus } from '@/types/lead';
import PageHeader from '@/components/shared/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Button from '@/components/ui/Button.vue';
import LoadingSpinner from '@/components/shared/LoadingSpinner.vue';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

const toast = useToast();

const overview = ref<ReportsOverview | null>(null);
const leadsReport = ref<LeadsReport | null>(null);
const loading = ref(false);
const leadsReportLoading = ref(false);
const exportingLeads = ref(false);
const exportingSales = ref(false);

const chartData = computed(() => {
    if (!leadsReport.value?.by_status) return null;
    const byStatus = leadsReport.value.by_status;
    const labels = Object.keys(byStatus).map((s) => LEAD_STATUS_LABELS[s as LeadStatus] ?? s);
    const values = Object.values(byStatus);
    return {
        labels,
        datasets: [{
            label: 'Leads',
            data: values,
            backgroundColor: 'rgba(31, 111, 235, 0.7)',
            borderRadius: 6,
            borderSkipped: false,
        }],
    };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        y: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, 0.1)' } },
        x: { grid: { display: false } },
    },
};

async function fetchOverview(): Promise<void> {
    loading.value = true;
    try {
        overview.value = await apiGet<ReportsOverview>('/reports/overview');
    } catch {
        overview.value = null;
    } finally {
        loading.value = false;
    }
}

async function fetchLeadsReport(): Promise<void> {
    leadsReportLoading.value = true;
    try {
        leadsReport.value = await apiGet<LeadsReport>('/reports/leads');
    } catch {
        leadsReport.value = null;
    } finally {
        leadsReportLoading.value = false;
    }
}

async function exportReport(type: 'leads' | 'sales'): Promise<void> {
    const loadingRef = type === 'leads' ? exportingLeads : exportingSales;
    loadingRef.value = true;
    try {
        await downloadAuthenticated(`/reports/${type}?format=csv`, `${type}-report.csv`);
        toast.success(`${type} report exported`);
    } catch (e) {
        toast.error(getApiError(e));
    } finally {
        loadingRef.value = false;
    }
}

onMounted(async () => {
    await fetchOverview();
    await fetchLeadsReport();
});
</script>
