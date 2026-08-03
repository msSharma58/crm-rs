<template>
    <div>
        <PageHeader title="Settings" description="Manage your organization profile" />

        <Card class="max-w-2xl">
            <form class="space-y-4" @submit.prevent="handleSave">
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Organization Name</label>
                    <Input v-model="form.name" required />
                </div>
                <div class="grid grid-cols-2 gap-4">
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
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">City</label>
                        <Input v-model="form.city" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Country</label>
                        <Input v-model="form.country" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
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
    </div>
</template>

<script setup lang="ts">
import { reactive, ref, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { apiPut, getApiError } from '@/lib/api';
import type { Organization } from '@/types';
import PageHeader from '@/components/shared/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Input from '@/components/ui/Input.vue';
import Button from '@/components/ui/Button.vue';

const auth = useAuthStore();
const saving = ref(false);
const saved = ref(false);
const error = ref('');

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

onMounted(loadForm);
</script>
