<template>
    <div>
        <PageHeader title="Reports" description="Analytics and exportable reports">
            <template #actions>
                <Button variant="outline" @click="exportReport('leads')">
                    <Download class="h-4 w-4" />
                    Export Leads
                </Button>
                <Button variant="outline" @click="exportReport('sales')">
                    <Download class="h-4 w-4" />
                    Export Sales
                </Button>
            </template>
        </PageHeader>

        <div class="grid gap-6 lg:grid-cols-2">
            <Card>
                <template #header>
                    <h3 class="text-base font-semibold">Sales Overview</h3>
                </template>
                <div class="flex h-48 items-center justify-center rounded-lg bg-muted/50">
                    <div class="text-center">
                        <BarChart3 class="mx-auto h-10 w-10 text-muted-foreground" />
                        <p class="mt-2 text-sm text-muted-foreground">Sales chart placeholder</p>
                    </div>
                </div>
            </Card>

            <Card>
                <template #header>
                    <h3 class="text-base font-semibold">Lead Funnel</h3>
                </template>
                <div class="flex h-48 items-center justify-center rounded-lg bg-muted/50">
                    <div class="text-center">
                        <TrendingUp class="mx-auto h-10 w-10 text-muted-foreground" />
                        <p class="mt-2 text-sm text-muted-foreground">Funnel chart placeholder</p>
                    </div>
                </div>
            </Card>

            <Card class="lg:col-span-2">
                <template #header>
                    <h3 class="text-base font-semibold">Report Summary</h3>
                </template>
                <div v-if="loading" class="flex justify-center py-8">
                    <LoadingSpinner class="h-6 w-6" />
                </div>
                <div v-else-if="overview" class="grid gap-4 sm:grid-cols-4">
                    <div class="rounded-lg bg-muted p-4 text-center">
                        <p class="text-2xl font-semibold">{{ overview.total_leads ?? '—' }}</p>
                        <p class="text-xs text-muted-foreground">Total Leads</p>
                    </div>
                    <div class="rounded-lg bg-muted p-4 text-center">
                        <p class="text-2xl font-semibold">{{ overview.total_bookings ?? '—' }}</p>
                        <p class="text-xs text-muted-foreground">Bookings</p>
                    </div>
                    <div class="rounded-lg bg-muted p-4 text-center">
                        <p class="text-2xl font-semibold">{{ overview.total_revenue ?? '—' }}</p>
                        <p class="text-xs text-muted-foreground">Revenue</p>
                    </div>
                    <div class="rounded-lg bg-muted p-4 text-center">
                        <p class="text-2xl font-semibold">{{ overview.conversion_rate ?? '—' }}%</p>
                        <p class="text-xs text-muted-foreground">Conversion</p>
                    </div>
                </div>
            </Card>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Download, BarChart3, TrendingUp } from 'lucide-vue-next';
import { apiGet } from '@/lib/api';
import PageHeader from '@/components/shared/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Button from '@/components/ui/Button.vue';
import LoadingSpinner from '@/components/shared/LoadingSpinner.vue';

const overview = ref<Record<string, number> | null>(null);
const loading = ref(false);

async function fetchOverview(): Promise<void> {
    loading.value = true;
    try {
        overview.value = await apiGet<Record<string, number>>('/reports/overview');
    } catch {
        overview.value = null;
    } finally {
        loading.value = false;
    }
}

function exportReport(type: string): void {
    window.open(`/api/v1/reports/${type}?format=csv`, '_blank');
}

onMounted(() => fetchOverview());
</script>
