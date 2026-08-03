<template>
    <div>
        <PageHeader title="Dashboard" description="Overview of your sales pipeline and operations" />

        <div v-if="dashboard.loading" class="flex justify-center py-20">
            <LoadingSpinner class="h-8 w-8" />
        </div>

        <template v-else-if="dashboard.kpis">
            <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <KpiCard
                    label="Total Leads"
                    :value="dashboard.kpis.leads.total"
                    :icon="Users"
                    :delay="0"
                />
                <KpiCard
                    label="New This Month"
                    :value="dashboard.kpis.leads.new_this_month"
                    :icon="TrendingUp"
                    icon-bg="bg-emerald-500/10"
                    icon-color="text-emerald-500"
                    :delay="1"
                />
                <KpiCard
                    label="Revenue Collected"
                    :value="dashboard.kpis.sales.revenue_collected"
                    suffix=" NPR"
                    :icon="DollarSign"
                    icon-bg="bg-amber-500/10"
                    icon-color="text-amber-500"
                    :delay="2"
                />
                <KpiCard
                    label="Hot Leads"
                    :value="dashboard.kpis.leads.hot_leads"
                    :icon="Flame"
                    icon-bg="bg-red-500/10"
                    icon-color="text-red-500"
                    :delay="3"
                />
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <Card class="lg:col-span-2">
                    <template #header>
                        <h3 class="text-base font-semibold text-foreground">Leads by Status</h3>
                    </template>
                    <div class="h-64">
                        <Bar v-if="chartData" :data="chartData" :options="chartOptions" />
                    </div>
                </Card>

                <Card>
                    <template #header>
                        <h3 class="text-base font-semibold text-foreground">Top Sources</h3>
                    </template>
                    <div class="space-y-3">
                        <div
                            v-for="(source, idx) in topSources"
                            :key="source.name"
                            class="flex items-center justify-between"
                        >
                            <div class="flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded text-xs font-medium text-brand-600 bg-brand-500/10">
                                    {{ idx + 1 }}
                                </span>
                                <span class="text-sm capitalize text-foreground">{{ source.name.replace(/_/g, ' ') }}</span>
                            </div>
                            <span class="text-sm font-medium text-muted-foreground">{{ source.count }}</span>
                        </div>
                        <p v-if="!topSources.length" class="text-sm text-muted-foreground">No source data yet</p>
                    </div>
                </Card>
            </div>

            <div class="mt-6 grid gap-6 lg:grid-cols-2">
                <Card>
                    <template #header>
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-semibold text-foreground">Today's Follow-ups</h3>
                            <RouterLink to="/follow-ups" class="text-sm text-brand-500 hover:underline">View all</RouterLink>
                        </div>
                    </template>
                    <div v-if="followUpsLoading" class="flex justify-center py-8">
                        <LoadingSpinner class="h-6 w-6" />
                    </div>
                    <div v-else-if="todayFollowUps.length" class="space-y-3">
                        <div
                            v-for="fu in todayFollowUps"
                            :key="fu.id"
                            class="flex items-center justify-between rounded-lg border border-border p-3"
                        >
                            <div>
                                <p class="text-sm font-medium text-foreground">{{ fu.title }}</p>
                                <p class="text-xs text-muted-foreground">{{ fu.lead?.name ?? fu.customer?.name }}</p>
                            </div>
                            <StatusBadge :status="fu.status" />
                        </div>
                    </div>
                    <p v-else class="py-4 text-sm text-muted-foreground">No follow-ups scheduled for today</p>
                </Card>

                <Card>
                    <template #header>
                        <h3 class="text-base font-semibold text-foreground">Quick Stats</h3>
                    </template>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="rounded-lg bg-muted p-4">
                            <p class="text-2xl font-semibold text-foreground">{{ dashboard.kpis.operations.visits_today }}</p>
                            <p class="text-xs text-muted-foreground">Visits Today</p>
                        </div>
                        <div class="rounded-lg bg-muted p-4">
                            <p class="text-2xl font-semibold text-foreground">{{ dashboard.kpis.operations.pending_follow_ups }}</p>
                            <p class="text-xs text-muted-foreground">Pending Follow-ups</p>
                        </div>
                        <div class="rounded-lg bg-muted p-4">
                            <p class="text-2xl font-semibold text-foreground">{{ dashboard.kpis.inventory.available }}</p>
                            <p class="text-xs text-muted-foreground">Available Units</p>
                        </div>
                        <div class="rounded-lg bg-muted p-4">
                            <p class="text-2xl font-semibold text-foreground">{{ dashboard.kpis.sales.pending_payments.toLocaleString() }}</p>
                            <p class="text-xs text-muted-foreground">Pending Payments (NPR)</p>
                        </div>
                    </div>
                </Card>
            </div>
        </template>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
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
import { Users, TrendingUp, DollarSign, Flame } from '@lucide/vue';
import { useDashboardStore } from '@/stores/dashboard';
import { apiGet } from '@/lib/api';
import PageHeader from '@/components/shared/PageHeader.vue';
import KpiCard from '@/components/shared/KpiCard.vue';
import Card from '@/components/ui/Card.vue';
import StatusBadge from '@/components/shared/StatusBadge.vue';
import LoadingSpinner from '@/components/shared/LoadingSpinner.vue';
import { LEAD_STATUS_LABELS, type LeadStatus } from '@/types/lead';
import type { FollowUp, Paginated } from '@/types';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

const dashboard = useDashboardStore();
const todayFollowUps = ref<FollowUp[]>([]);
const followUpsLoading = ref(false);

const chartData = computed(() => {
    if (!dashboard.kpis) return null;
    const byStatus = dashboard.kpis.leads.by_status;
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

const topSources = computed(() => {
    if (!dashboard.kpis) return [];
    return Object.entries(dashboard.kpis.leads.by_status)
        .map(([name, count]) => ({ name, count }))
        .sort((a, b) => b.count - a.count)
        .slice(0, 5);
});

async function fetchTodayFollowUps(): Promise<void> {
    followUpsLoading.value = true;
    try {
        const response = await apiGet<Paginated<FollowUp>>('/follow-ups', {
            params: { status: 'pending', per_page: 5 },
        });
        todayFollowUps.value = response.data;
    } catch {
        todayFollowUps.value = [];
    } finally {
        followUpsLoading.value = false;
    }
}

onMounted(async () => {
    await dashboard.fetchKpis();
    await fetchTodayFollowUps();
});
</script>
