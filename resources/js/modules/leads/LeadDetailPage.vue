<template>
    <div>
        <div v-if="leadsStore.loading && !leadsStore.currentLead" class="flex justify-center py-20">
            <LoadingSpinner class="h-8 w-8" />
        </div>

        <template v-else-if="lead">
            <div class="mb-6">
                <button class="mb-4 flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground" @click="router.back()">
                    <ArrowLeft class="h-4 w-4" /> Back
                </button>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-foreground">{{ lead.name }}</h1>
                        <div class="mt-2 flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
                            <span v-if="lead.phone">{{ lead.phone }}</span>
                            <span v-if="lead.email">{{ lead.email }}</span>
                            <span v-if="lead.location">{{ lead.location }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <Select v-model="statusValue" class="w-48" @change="updateStatus">
                            <option v-for="s in LEAD_STATUSES" :key="s" :value="s">{{ LEAD_STATUS_LABELS[s] }}</option>
                        </Select>
                    </div>
                </div>
            </div>

            <div class="mb-6 grid gap-4 sm:grid-cols-4">
                <div class="rounded-lg border border-border bg-card p-4">
                    <p class="text-xs text-muted-foreground">Source</p>
                    <p class="mt-1 text-sm font-medium capitalize">{{ lead.source?.replace(/_/g, ' ') ?? '—' }}</p>
                </div>
                <div class="rounded-lg border border-border bg-card p-4">
                    <p class="text-xs text-muted-foreground">Priority</p>
                    <p class="mt-1 text-sm font-medium capitalize">{{ lead.priority ?? '—' }}</p>
                </div>
                <div class="rounded-lg border border-border bg-card p-4">
                    <p class="text-xs text-muted-foreground">Budget</p>
                    <p class="mt-1 text-sm font-medium">{{ formatCurrency(lead.budget) }}</p>
                </div>
                <div class="rounded-lg border border-border bg-card p-4">
                    <p class="text-xs text-muted-foreground">AI Score</p>
                    <p class="mt-1 text-sm font-medium">{{ lead.ai_score ?? '—' }}</p>
                </div>
            </div>

            <div class="border-b border-border">
                <nav class="flex gap-6">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        class="border-b-2 pb-3 text-sm font-medium transition-colors"
                        :class="activeTab === tab.id
                            ? 'border-brand-500 text-brand-500'
                            : 'border-transparent text-muted-foreground hover:text-foreground'"
                        @click="activeTab = tab.id"
                    >
                        {{ tab.label }}
                    </button>
                </nav>
            </div>

            <div class="mt-6">
                <div v-if="activeTab === 'timeline'" class="space-y-4">
                    <div
                        v-for="activity in lead.activities ?? []"
                        :key="activity.id"
                        class="flex gap-4 rounded-lg border border-border p-4"
                    >
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-500/10">
                            <Activity class="h-4 w-4 text-brand-500" />
                        </div>
                        <div>
                            <p class="text-sm text-foreground">{{ activity.description }}</p>
                            <p class="mt-1 text-xs text-muted-foreground">{{ formatDateTime(activity.created_at) }}</p>
                        </div>
                    </div>
                    <p v-if="!lead.activities?.length" class="text-sm text-muted-foreground">No activity yet</p>
                </div>

                <div v-else-if="activeTab === 'notes'">
                    <div class="rounded-lg border border-border bg-card p-4">
                        <p class="whitespace-pre-wrap text-sm text-foreground">{{ lead.notes || 'No notes added yet.' }}</p>
                    </div>
                </div>

                <div v-else-if="activeTab === 'tasks'">
                    <EmptyState title="No tasks" description="Tasks linked to this lead will appear here" />
                </div>
            </div>
        </template>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { ArrowLeft, Activity } from 'lucide-vue-next';
import { useLeadsStore } from '@/stores/leads';
import { LEAD_STATUSES, LEAD_STATUS_LABELS } from '@/types/lead';
import { formatCurrency, formatDateTime } from '@/lib/utils';
import Select from '@/components/ui/Select.vue';
import LoadingSpinner from '@/components/shared/LoadingSpinner.vue';
import EmptyState from '@/components/shared/EmptyState.vue';
import type { LeadStatus } from '@/types/lead';

const route = useRoute();
const router = useRouter();
const leadsStore = useLeadsStore();

const activeTab = ref('timeline');
const statusValue = ref('');

const lead = computed(() => leadsStore.currentLead);

const tabs = [
    { id: 'timeline', label: 'Timeline' },
    { id: 'notes', label: 'Notes' },
    { id: 'tasks', label: 'Tasks' },
];

watch(lead, (l) => {
    if (l) statusValue.value = l.status;
}, { immediate: true });

async function updateStatus(): Promise<void> {
    if (!lead.value) return;
    await leadsStore.updateStatus(lead.value.id, statusValue.value as LeadStatus);
}

onMounted(() => {
    const id = Number(route.params.id);
    if (id) leadsStore.fetchLead(id);
});
</script>
