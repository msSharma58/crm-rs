<template>
    <div>
        <div v-if="loading" class="flex justify-center py-20">
            <LoadingSpinner class="h-8 w-8" />
        </div>
        <template v-else-if="customer">
            <button class="mb-4 flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground" @click="router.back()">
                <ArrowLeft class="h-4 w-4" /> Back
            </button>
            <PageHeader :title="customer.name" :description="customer.email ?? customer.phone ?? ''" />
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-lg border border-border bg-card p-4">
                    <p class="text-xs text-muted-foreground">Phone</p>
                    <p class="mt-1 font-medium">{{ customer.phone ?? '—' }}</p>
                </div>
                <div class="rounded-lg border border-border bg-card p-4">
                    <p class="text-xs text-muted-foreground">Location</p>
                    <p class="mt-1 font-medium">{{ customer.location ?? '—' }}</p>
                </div>
                <div class="rounded-lg border border-border bg-card p-4">
                    <p class="text-xs text-muted-foreground">Occupation</p>
                    <p class="mt-1 font-medium">{{ customer.occupation ?? '—' }}</p>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { ArrowLeft } from '@lucide/vue';
import { apiGet } from '@/lib/api';
import type { Customer } from '@/types';
import PageHeader from '@/components/shared/PageHeader.vue';
import LoadingSpinner from '@/components/shared/LoadingSpinner.vue';

const route = useRoute();
const router = useRouter();
const customer = ref<Customer | null>(null);
const loading = ref(false);

onMounted(async () => {
    loading.value = true;
    try {
        customer.value = await apiGet<Customer>(`/customers/${route.params.id}`);
    } finally {
        loading.value = false;
    }
});
</script>
