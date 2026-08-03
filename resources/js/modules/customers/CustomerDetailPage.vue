<template>
    <div>
        <div v-if="loading" class="flex justify-center py-20">
            <LoadingSpinner class="h-8 w-8" />
        </div>
        <template v-else-if="customer">
            <button class="mb-4 flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground" @click="router.back()">
                <ArrowLeft class="h-4 w-4" /> Back
            </button>

            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-foreground">{{ customer.name }}</h1>
                    <p class="mt-1 text-sm text-muted-foreground">{{ customer.email ?? customer.phone ?? '' }}</p>
                </div>
                <Button v-if="!editing" variant="outline" @click="startEdit">Edit Contact</Button>
                <div v-else class="flex gap-2">
                    <Button variant="outline" @click="cancelEdit">Cancel</Button>
                    <Button :loading="saving" @click="saveContact">Save</Button>
                </div>
            </div>

            <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg border border-border bg-card p-4">
                    <p class="text-xs text-muted-foreground">Phone</p>
                    <Input v-if="editing" v-model="editForm.phone" class="mt-1" />
                    <p v-else class="mt-1 font-medium">{{ customer.phone ?? '—' }}</p>
                </div>
                <div class="rounded-lg border border-border bg-card p-4">
                    <p class="text-xs text-muted-foreground">Email</p>
                    <Input v-if="editing" v-model="editForm.email" type="email" class="mt-1" />
                    <p v-else class="mt-1 font-medium">{{ customer.email ?? '—' }}</p>
                </div>
                <div class="rounded-lg border border-border bg-card p-4">
                    <p class="text-xs text-muted-foreground">Location</p>
                    <Input v-if="editing" v-model="editForm.location" class="mt-1" />
                    <p v-else class="mt-1 font-medium">{{ customer.location ?? '—' }}</p>
                </div>
                <div class="rounded-lg border border-border bg-card p-4">
                    <p class="text-xs text-muted-foreground">Occupation</p>
                    <Input v-if="editing" v-model="editForm.occupation" class="mt-1" />
                    <p v-else class="mt-1 font-medium">{{ customer.occupation ?? '—' }}</p>
                </div>
            </div>

            <div class="mb-8">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-foreground">Family Members</h2>
                    <Button size="sm" variant="outline" @click="showAddFamily = true">Add Member</Button>
                </div>
                <div v-if="customer.family_members?.length" class="space-y-2">
                    <div
                        v-for="member in customer.family_members"
                        :key="member.id"
                        class="flex items-center justify-between rounded-lg border border-border p-3"
                    >
                        <div>
                            <p class="text-sm font-medium">{{ member.name }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ member.relation ?? '—' }} · {{ member.phone ?? 'No phone' }}
                            </p>
                        </div>
                        <Button size="sm" variant="ghost" class="text-red-600" @click="removeFamilyMember(member.id)">
                            Remove
                        </Button>
                    </div>
                </div>
                <p v-else class="text-sm text-muted-foreground">No family members added</p>
            </div>

            <div v-if="customer.bookings?.length">
                <h2 class="mb-4 text-lg font-semibold text-foreground">Bookings</h2>
                <div class="space-y-2">
                    <div
                        v-for="booking in customer.bookings"
                        :key="booking.id"
                        class="flex items-center justify-between rounded-lg border border-border p-3"
                    >
                        <div>
                            <p class="text-sm font-medium">{{ booking.code }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ booking.unit?.code ?? '—' }} · {{ formatCurrency(booking.total_amount) }}
                            </p>
                        </div>
                        <StatusBadge :status="booking.status" />
                    </div>
                </div>
            </div>
        </template>

        <Modal :open="showAddFamily" title="Add Family Member" @close="showAddFamily = false">
            <form class="space-y-4" @submit.prevent="addFamilyMember">
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Name *</label>
                    <Input v-model="familyForm.name" required />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Relation</label>
                        <Input v-model="familyForm.relation" placeholder="e.g. Spouse" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Phone</label>
                        <Input v-model="familyForm.phone" />
                    </div>
                </div>
            </form>
            <template #footer>
                <Button variant="outline" @click="showAddFamily = false">Cancel</Button>
                <Button :loading="addingFamily" @click="addFamilyMember">Add</Button>
            </template>
        </Modal>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { ArrowLeft } from '@lucide/vue';
import { apiGet, apiPut, apiPost, apiDelete, getApiError } from '@/lib/api';
import { formatCurrency } from '@/lib/utils';
import { useToast } from '@/composables/useToast';
import type { Customer } from '@/types';
import LoadingSpinner from '@/components/shared/LoadingSpinner.vue';
import StatusBadge from '@/components/shared/StatusBadge.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import Modal from '@/components/ui/Modal.vue';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const customer = ref<Customer | null>(null);
const loading = ref(false);
const editing = ref(false);
const saving = ref(false);
const showAddFamily = ref(false);
const addingFamily = ref(false);

const editForm = reactive({
    phone: '',
    email: '',
    location: '',
    occupation: '',
});

const familyForm = reactive({
    name: '',
    relation: '',
    phone: '',
});

async function loadCustomer(): Promise<void> {
    loading.value = true;
    try {
        customer.value = await apiGet<Customer>(`/customers/${route.params.id}`);
    } finally {
        loading.value = false;
    }
}

function startEdit(): void {
    if (!customer.value) return;
    editForm.phone = customer.value.phone ?? '';
    editForm.email = customer.value.email ?? '';
    editForm.location = customer.value.location ?? '';
    editForm.occupation = customer.value.occupation ?? '';
    editing.value = true;
}

function cancelEdit(): void {
    editing.value = false;
}

async function saveContact(): Promise<void> {
    if (!customer.value) return;
    saving.value = true;
    try {
        customer.value = await apiPut<Customer>(`/customers/${customer.value.id}`, editForm);
        editing.value = false;
        toast.success('Contact info updated');
    } catch (e) {
        toast.error(getApiError(e));
    } finally {
        saving.value = false;
    }
}

async function addFamilyMember(): Promise<void> {
    if (!customer.value) return;
    addingFamily.value = true;
    try {
        await apiPost(`/customers/${customer.value.id}/family`, familyForm);
        showAddFamily.value = false;
        Object.assign(familyForm, { name: '', relation: '', phone: '' });
        toast.success('Family member added');
        await loadCustomer();
    } catch (e) {
        toast.error(getApiError(e));
    } finally {
        addingFamily.value = false;
    }
}

async function removeFamilyMember(memberId: number): Promise<void> {
    if (!customer.value) return;
    try {
        await apiDelete(`/customers/${customer.value.id}/family/${memberId}`);
        toast.success('Family member removed');
        await loadCustomer();
    } catch (e) {
        toast.error(getApiError(e));
    }
}

onMounted(() => loadCustomer());
</script>
