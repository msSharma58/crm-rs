<template>
    <div>
        <PageHeader title="Lead Board" description="Drag and drop leads to update their status">
            <template #actions>
                <Button variant="outline" @click="router.push('/leads')">
                    <List class="h-4 w-4" />
                    List View
                </Button>
            </template>
        </PageHeader>

        <div v-if="leadsStore.boardLoading" class="flex justify-center py-20">
            <LoadingSpinner class="h-8 w-8" />
        </div>

        <div v-else class="flex gap-4 overflow-x-auto pb-4">
            <div
                v-for="column in columns"
                :key="column.status"
                class="flex w-72 shrink-0 flex-col rounded-xl border border-border bg-muted/30"
                @dragover.prevent
                @drop="onDrop($event, column.status)"
            >
                <div class="flex items-center justify-between border-b border-border px-4 py-3">
                    <div class="flex items-center gap-2">
                        <StatusBadge :status="column.status" type="lead" />
                        <span class="text-xs font-medium text-muted-foreground">{{ column.leads.length }}</span>
                    </div>
                </div>

                <div class="flex-1 space-y-2 overflow-y-auto p-3" style="min-height: 400px; max-height: calc(100vh - 240px)">
                    <div
                        v-for="lead in column.leads"
                        :key="lead.id"
                        draggable="true"
                        class="kanban-card-lift cursor-grab rounded-lg border border-border bg-card p-3 shadow-sm active:cursor-grabbing"
                        :class="{ dragging: draggingId === lead.id }"
                        @dragstart="onDragStart($event, lead)"
                        @dragend="draggingId = null"
                        @click="router.push(`/leads/${lead.id}`)"
                    >
                        <p class="text-sm font-medium text-foreground">{{ lead.name }}</p>
                        <p v-if="lead.phone" class="mt-1 text-xs text-muted-foreground">{{ lead.phone }}</p>
                        <div class="mt-2 flex items-center justify-between">
                            <span v-if="lead.priority" class="text-xs capitalize text-muted-foreground">{{ lead.priority }}</span>
                            <span v-if="lead.budget" class="text-xs font-medium text-brand-500">{{ formatCurrency(lead.budget) }}</span>
                        </div>
                    </div>
                    <p v-if="!column.leads.length" class="py-8 text-center text-xs text-muted-foreground">No leads</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { List } from 'lucide-vue-next';
import { useLeadsStore } from '@/stores/leads';
import { LEAD_STATUSES } from '@/types/lead';
import { formatCurrency } from '@/lib/utils';
import type { Lead } from '@/types';
import type { LeadStatus } from '@/types/lead';
import PageHeader from '@/components/shared/PageHeader.vue';
import StatusBadge from '@/components/shared/StatusBadge.vue';
import Button from '@/components/ui/Button.vue';
import LoadingSpinner from '@/components/shared/LoadingSpinner.vue';

const router = useRouter();
const leadsStore = useLeadsStore();
const draggingId = ref<number | null>(null);
let draggedLead: Lead | null = null;

const columns = computed(() => {
    const boardMap = new Map(leadsStore.board.map((col) => [col.status, col.leads]));
    return LEAD_STATUSES.map((status) => ({
        status,
        leads: boardMap.get(status) ?? [],
    }));
});

function onDragStart(_event: DragEvent, lead: Lead): void {
    draggedLead = lead;
    draggingId.value = lead.id;
}

async function onDrop(_event: DragEvent, status: LeadStatus): Promise<void> {
    if (!draggedLead || draggedLead.status === status) return;
    await leadsStore.updateStatus(draggedLead.id, status);
    draggedLead = null;
    draggingId.value = null;
}

onMounted(() => leadsStore.fetchBoard());
</script>
