<template>
    <div class="space-y-8">
        <PageHeader title="Settings" description="Organization profile and live channel integrations" />

        <Card class="max-w-2xl">
            <template #header>
                <h2 class="text-lg font-semibold">Organization</h2>
                <p class="text-sm text-muted-foreground">Profile shown across the CRM</p>
            </template>
            <form class="space-y-4" @submit.prevent="handleSave">
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Organization Name</label>
                    <Input v-model="form.name" required />
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Email</label>
                        <Input v-model="form.email" type="email" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Phone</label>
                        <Input v-model="form.phone" />
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Address</label>
                    <Input v-model="form.address" />
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">City</label>
                        <Input v-model="form.city" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Country</label>
                        <Input v-model="form.country" />
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Timezone</label>
                        <Input v-model="form.timezone" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Currency</label>
                        <Input v-model="form.currency" />
                    </div>
                </div>

                <div v-if="saved" class="text-sm text-emerald-600">Settings saved successfully</div>
                <div v-if="error" class="text-sm text-red-600">{{ error }}</div>

                <Button type="submit" :loading="saving">Save Changes</Button>
            </form>
        </Card>

        <div class="grid max-w-5xl gap-6 lg:grid-cols-2">
            <Card>
                <template #header>
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold">Facebook Lead Ads</h2>
                            <p class="text-sm text-muted-foreground">Live leadgen webhook + form backfill</p>
                        </div>
                        <label class="flex items-center gap-2 text-sm">
                            <input v-model="facebook.is_active" type="checkbox" class="rounded border-border" />
                            Active
                        </label>
                    </div>
                </template>

                <form class="space-y-4" @submit.prevent="saveFacebook">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Page ID</label>
                        <Input v-model="facebook.page_id" placeholder="Meta Page ID" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Page Access Token</label>
                        <Input
                            v-model="facebook.page_access_token"
                            type="password"
                            :placeholder="facebook.has_page_access_token ? '•••• saved — paste to replace' : 'EAAxxxx…'"
                        />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Webhook Verify Token</label>
                        <Input v-model="facebook.webhook_verify_token" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Webhook URL</label>
                        <div class="flex gap-2">
                            <Input :model-value="facebook.webhook_url" readonly />
                            <Button type="button" variant="outline" size="sm" @click="copyText(facebook.webhook_url)">Copy</Button>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_auto]">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Form ID (backfill)</label>
                            <Input v-model="facebook.default_form_id" placeholder="Lead form ID" />
                        </div>
                        <div class="flex items-end">
                            <Button type="button" variant="secondary" :loading="syncingForm" @click="syncFacebookForm">
                                Sync Form
                            </Button>
                        </div>
                    </div>
                    <p v-if="facebook.last_synced_at" class="text-xs text-muted-foreground">
                        Last synced {{ formatDateTime(facebook.last_synced_at) }}
                    </p>
                    <Button type="submit" :loading="savingFacebook">Save Facebook</Button>
                </form>
            </Card>

            <Card>
                <template #header>
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold">WhatsApp Cloud</h2>
                            <p class="text-sm text-muted-foreground">Inbound messages create/update leads</p>
                        </div>
                        <label class="flex items-center gap-2 text-sm">
                            <input v-model="whatsapp.is_active" type="checkbox" class="rounded border-border" />
                            Active
                        </label>
                    </div>
                </template>

                <form class="space-y-4" @submit.prevent="saveWhatsApp">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Phone Number ID</label>
                        <Input v-model="whatsapp.phone_number_id" placeholder="WhatsApp phone_number_id" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Display Phone Number</label>
                        <Input v-model="whatsapp.display_phone_number" placeholder="+977…" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Access Token</label>
                        <Input
                            v-model="whatsapp.access_token"
                            type="password"
                            :placeholder="whatsapp.has_access_token ? '•••• saved — paste to replace' : 'EAAxxxx…'"
                        />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">WABA ID (optional)</label>
                        <Input v-model="whatsapp.waba_id" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Webhook Verify Token</label>
                        <Input v-model="whatsapp.webhook_verify_token" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Webhook URL</label>
                        <div class="flex gap-2">
                            <Input :model-value="whatsapp.webhook_url" readonly />
                            <Button type="button" variant="outline" size="sm" @click="copyText(whatsapp.webhook_url)">Copy</Button>
                        </div>
                    </div>
                    <Button type="submit" :loading="savingWhatsApp">Save WhatsApp</Button>
                </form>
            </Card>
        </div>
    </div>
</template>

<script setup lang="ts">
import { reactive, ref, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { apiGet, apiPost, apiPut, getApiError } from '@/lib/api';
import { formatDateTime } from '@/lib/utils';
import { useToast } from '@/composables/useToast';
import type { IntegrationSetting, Organization } from '@/types';
import PageHeader from '@/components/shared/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Input from '@/components/ui/Input.vue';
import Button from '@/components/ui/Button.vue';

const auth = useAuthStore();
const toast = useToast();
const saving = ref(false);
const saved = ref(false);
const error = ref('');
const savingFacebook = ref(false);
const savingWhatsApp = ref(false);
const syncingForm = ref(false);

const form = reactive({
    name: '',
    email: '',
    phone: '',
    address: '',
    city: '',
    country: '',
    timezone: '',
    currency: '',
});

const facebook = reactive({
    is_active: false,
    page_id: '',
    page_access_token: '',
    webhook_verify_token: '',
    webhook_url: `${window.location.origin}/api/v1/webhooks/facebook`,
    default_form_id: '',
    has_page_access_token: false,
    last_synced_at: null as string | null,
});

const whatsapp = reactive({
    is_active: false,
    phone_number_id: '',
    display_phone_number: '',
    access_token: '',
    waba_id: '',
    webhook_verify_token: '',
    webhook_url: `${window.location.origin}/api/v1/webhooks/whatsapp`,
    has_access_token: false,
});

function loadForm(): void {
    const org = auth.organization;
    if (!org) return;
    Object.assign(form, {
        name: org.name ?? '',
        email: org.email ?? '',
        phone: org.phone ?? '',
        address: org.address ?? '',
        city: org.city ?? '',
        country: org.country ?? '',
        timezone: org.timezone ?? '',
        currency: org.currency ?? '',
    });
}

function applyIntegration(setting: IntegrationSetting): void {
    const settings = setting.settings ?? {};
    if (setting.provider === 'facebook') {
        facebook.is_active = setting.is_active;
        facebook.page_id = String(settings.page_id ?? '');
        facebook.webhook_verify_token = setting.webhook_verify_token ?? '';
        facebook.webhook_url = setting.webhook_url || facebook.webhook_url;
        facebook.default_form_id = String(settings.default_form_id ?? '');
        facebook.has_page_access_token = setting.has_page_access_token;
        facebook.last_synced_at = setting.last_synced_at;
        facebook.page_access_token = '';
    }
    if (setting.provider === 'whatsapp') {
        whatsapp.is_active = setting.is_active;
        whatsapp.phone_number_id = String(settings.phone_number_id ?? '');
        whatsapp.display_phone_number = String(settings.display_phone_number ?? '');
        whatsapp.waba_id = String(settings.waba_id ?? '');
        whatsapp.webhook_verify_token = setting.webhook_verify_token ?? '';
        whatsapp.webhook_url = setting.webhook_url || whatsapp.webhook_url;
        whatsapp.has_access_token = setting.has_access_token;
        whatsapp.access_token = '';
    }
}

async function loadIntegrations(): Promise<void> {
    try {
        const items = await apiGet<IntegrationSetting[]>('/integrations');
        items.forEach(applyIntegration);
    } catch (e) {
        toast.error(getApiError(e));
    }
}

async function handleSave(): Promise<void> {
    if (!auth.organization) return;
    saving.value = true;
    saved.value = false;
    error.value = '';
    try {
        await apiPut<Organization>(`/organizations/${auth.organization.id}`, form);
        await auth.fetchMe();
        saved.value = true;
    } catch (e) {
        error.value = getApiError(e);
    } finally {
        saving.value = false;
    }
}

async function saveFacebook(): Promise<void> {
    savingFacebook.value = true;
    try {
        const payload: Record<string, unknown> = {
            is_active: facebook.is_active,
            page_id: facebook.page_id || null,
            webhook_verify_token: facebook.webhook_verify_token || null,
            default_form_id: facebook.default_form_id || null,
        };
        if (facebook.page_access_token) {
            payload.page_access_token = facebook.page_access_token;
        }
        const savedSetting = await apiPut<IntegrationSetting>('/integrations/facebook', payload);
        applyIntegration(savedSetting);
        toast.success('Facebook integration saved');
    } catch (e) {
        toast.error(getApiError(e));
    } finally {
        savingFacebook.value = false;
    }
}

async function saveWhatsApp(): Promise<void> {
    savingWhatsApp.value = true;
    try {
        const payload: Record<string, unknown> = {
            is_active: whatsapp.is_active,
            phone_number_id: whatsapp.phone_number_id || null,
            display_phone_number: whatsapp.display_phone_number || null,
            waba_id: whatsapp.waba_id || null,
            webhook_verify_token: whatsapp.webhook_verify_token || null,
        };
        if (whatsapp.access_token) {
            payload.access_token = whatsapp.access_token;
        }
        const savedSetting = await apiPut<IntegrationSetting>('/integrations/whatsapp', payload);
        applyIntegration(savedSetting);
        toast.success('WhatsApp integration saved');
    } catch (e) {
        toast.error(getApiError(e));
    } finally {
        savingWhatsApp.value = false;
    }
}

async function syncFacebookForm(): Promise<void> {
    if (!facebook.default_form_id) {
        toast.error('Enter a Facebook form ID first');
        return;
    }
    syncingForm.value = true;
    try {
        const result = await apiPost<{ imported: number; skipped: number; errors: string[] }>(
            '/integrations/facebook/sync-form',
            { form_id: facebook.default_form_id },
        );
        toast.success(`Synced ${result.imported} leads (${result.skipped} skipped)`);
        await loadIntegrations();
    } catch (e) {
        toast.error(getApiError(e));
    } finally {
        syncingForm.value = false;
    }
}

async function copyText(value: string): Promise<void> {
    try {
        await navigator.clipboard.writeText(value);
        toast.success('Copied');
    } catch {
        toast.error('Could not copy');
    }
}

onMounted(async () => {
    loadForm();
    await loadIntegrations();
});
</script>
