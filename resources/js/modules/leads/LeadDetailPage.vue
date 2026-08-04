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
                            <span
                                v-if="lead.external_provider"
                                class="rounded-md bg-brand-500/10 px-2 py-0.5 text-xs font-medium capitalize text-brand-500"
                            >
                                {{ lead.external_provider }}
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <Select
                            v-model="statusValue"
                            class="w-48"
                            @update:model-value="updateStatus"
                        >
                            <option v-for="s in LEAD_STATUSES" :key="s" :value="s">{{ LEAD_STATUS_LABELS[s] }}</option>
                        </Select>
                        <Button
                            v-if="!lead.converted_at"
                            :loading="converting"
                            @click="handleConvert"
                        >
                            Convert to Customer
                        </Button>
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
                <nav class="flex gap-6 overflow-x-auto">
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
                            <p class="text-sm font-medium text-foreground">{{ activity.title || activity.description }}</p>
                            <p v-if="activity.title && activity.description" class="mt-1 text-sm text-muted-foreground">
                                {{ activity.description }}
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">{{ formatDateTime(activity.created_at) }}</p>
                        </div>
                    </div>
                    <p v-if="!lead.activities?.length" class="text-sm text-muted-foreground">No activity yet</p>
                </div>

                <div v-else-if="activeTab === 'whatsapp'" class="mx-auto max-w-2xl">
                    <div class="flex h-[28rem] flex-col rounded-lg border border-border bg-card">
                        <div class="border-b border-border px-4 py-3">
                            <p class="text-sm font-medium">WhatsApp conversation</p>
                            <p class="text-xs text-muted-foreground">{{ lead.phone || 'No phone on lead' }}</p>
                        </div>
                        <div ref="threadEl" class="flex-1 space-y-3 overflow-y-auto px-4 py-4">
                            <div v-if="loadingMessages" class="flex justify-center py-10">
                                <LoadingSpinner class="h-6 w-6" />
                            </div>
                            <template v-else>
                                <div
                                    v-for="msg in messages"
                                    :key="msg.id"
                                    class="flex"
                                    :class="msg.direction === 'outbound' ? 'justify-end' : 'justify-start'"
                                >
                                    <div
                                        class="max-w-[80%] rounded-2xl px-3 py-2 text-sm"
                                        :class="msg.direction === 'outbound'
                                            ? 'bg-brand-500 text-white'
                                            : 'bg-muted text-foreground'"
                                    >
                                        <p class="whitespace-pre-wrap">{{ msg.body }}</p>
                                        <p
                                            class="mt-1 text-[10px]"
                                            :class="msg.direction === 'outbound' ? 'text-white/70' : 'text-muted-foreground'"
                                        >
                                            {{ formatDateTime(msg.sent_at ?? msg.created_at) }}
                                            <span v-if="msg.status"> · {{ msg.status }}</span>
                                        </p>
                                    </div>
                                </div>
                                <p v-if="!messages.length" class="py-10 text-center text-sm text-muted-foreground">
                                    No WhatsApp messages yet
                                </p>
                            </template>
                        </div>
                        <form class="flex gap-2 border-t border-border p-3" @submit.prevent="sendWhatsApp">
                            <Input
                                v-model="waDraft"
                                class="flex-1"
                                placeholder="Type a WhatsApp message…"
                                :disabled="!lead.phone || sendingWa"
                            />
                            <Button type="submit" :loading="sendingWa" :disabled="!waDraft.trim() || !lead.phone">
                                Send
                            </Button>
                        </form>
                    </div>
                </div>

                <div v-else-if="activeTab === 'notes'">
                    <div class="rounded-lg border border-border bg-card p-4">
                        <textarea
                            v-model="notesValue"
                            rows="6"
                            class="w-full resize-y rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            placeholder="Add notes about this lead..."
                        />
                        <div class="mt-3 flex justify-end">
                            <Button :loading="savingNotes" size="sm" @click="saveNotes">Save Notes</Button>
                        </div>
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
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { ArrowLeft, Activity } from '@lucide/vue';
import { useLeadsStore } from '@/stores/leads';
import { LEAD_STATUSES, LEAD_STATUS_LABELS } from '@/types/lead';
import { formatCurrency, formatDateTime } from '@/lib/utils';
import { apiGet, apiPost, getApiError } from '@/lib/api';
import { useToast } from '@/composables/useToast';
import Select from '@/components/ui/Select.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import LoadingSpinner from '@/components/shared/LoadingSpinner.vue';
import EmptyState from '@/components/shared/EmptyState.vue';
import type { IntegrationMessage, LeadStatus } from '@/types';

const route = useRoute();
const router = useRouter();
const leadsStore = useLeadsStore();
const toast = useToast();

const activeTab = ref('timeline');
const statusValue = ref('');
const notesValue = ref('');
const converting = ref(false);
const savingNotes = ref(false);
const messages = ref<IntegrationMessage[]>([]);
const loadingMessages = ref(false);
const sendingWa = ref(false);
const waDraft = ref('');
const threadEl = ref<HTMLElement | null>(null);

const lead = computed(() => leadsStore.currentLead);

const tabs = [
    { id: 'timeline', label: 'Timeline' },
    { id: 'whatsapp', label: 'WhatsApp' },
    { id: 'notes', label: 'Notes' },
    { id: 'tasks', label: 'Tasks' },
];

watch(lead, (l) => {
    if (l) {
        statusValue.value = l.status;
        notesValue.value = l.notes ?? '';
    }
}, { immediate: true });

watch(activeTab, async (tab) => {
    if (tab === 'whatsapp' && lead.value) {
        await loadMessages();
    }
});

async function loadMessages(): Promise<void> {
    if (!lead.value) return;
    loadingMessages.value = true;
    try {
        messages.value = await apiGet<IntegrationMessage[]>(`/leads/${lead.value.id}/messages`);
        await nextTick();
        if (threadEl.value) {
            threadEl.value.scrollTop = threadEl.value.scrollHeight;
        }
    } catch (e) {
        toast.error(getApiError(e));
    } finally {
        loadingMessages.value = false;
    }
}

async function sendWhatsApp(): Promise<void> {
    if (!lead.value || !waDraft.value.trim()) return;
    sendingWa.value = true;
    try {
        const sent = await apiPost<IntegrationMessage>(`/leads/${lead.value.id}/whatsapp`, {
            message: waDraft.value.trim(),
        });
        messages.value = [...messages.value, sent];
        waDraft.value = '';
        await nextTick();
        if (threadEl.value) {
            threadEl.value.scrollTop = threadEl.value.scrollHeight;
        }
        toast.success('WhatsApp message sent');
    } catch (e) {
        toast.error(getApiError(e));
    } finally {
        sendingWa.value = false;
    }
}

async function updateStatus(): Promise<void> {
    if (!lead.value) return;
    try {
        await leadsStore.updateStatus(lead.value.id, statusValue.value as LeadStatus);
        toast.success('Status updated');
    } catch (e) {
        toast.error(getApiError(e));
    }
}

async function saveNotes(): Promise<void> {
    if (!lead.value) return;
    savingNotes.value = true;
    try {
        await leadsStore.updateLead(lead.value.id, { notes: notesValue.value });
        toast.success('Notes saved');
    } catch (e) {
        toast.error(getApiError(e));
    } finally {
        savingNotes.value = false;
    }
}

async function handleConvert(): Promise<void> {
    if (!lead.value) return;
    converting.value = true;
    try {
        const customer = await leadsStore.convertLead(lead.value.id);
        toast.success('Lead converted to customer');
        router.push(`/customers/${customer.id}`);
    } catch (e) {
        toast.error(getApiError(e));
    } finally {
        converting.value = false;
    }
}

onMounted(() => {
    const id = Number(route.params.id);
    if (id) leadsStore.fetchLead(id);
});
</script>
