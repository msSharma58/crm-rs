<template>
    <AuthLayout>
        <div class="w-full max-w-md">
            <div class="mb-8 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-500 shadow-lg shadow-brand-500/25">
                    <Building2 class="h-7 w-7 text-white" />
                </div>
                <h1 class="text-3xl font-bold tracking-tight text-foreground">Homora</h1>
                <p class="mt-2 text-sm text-muted-foreground">Real estate CRM built for modern sales teams</p>
            </div>

            <div class="rounded-2xl border border-border bg-card p-8 shadow-xl">
                <h2 class="mb-6 text-lg font-semibold text-foreground">Sign in to your account</h2>

                <form class="space-y-4" @submit.prevent="handleSubmit">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-foreground">Email</label>
                        <Input
                            v-model="email"
                            type="email"
                            placeholder="you@company.com"
                            required
                            autocomplete="email"
                        />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-foreground">Password</label>
                        <Input
                            v-model="password"
                            type="password"
                            placeholder="••••••••"
                            required
                            autocomplete="current-password"
                        />
                    </div>

                    <div v-if="error" class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600 dark:bg-red-900/20 dark:text-red-400">
                        {{ error }}
                    </div>

                    <Button type="submit" class="w-full" :loading="auth.loading" size="lg">
                        Sign in
                    </Button>
                </form>

                <p class="mt-6 text-center text-xs text-muted-foreground">
                    Demo: admin@tilottamahomes.com.np / password
                </p>
            </div>
        </div>
    </AuthLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { Building2 } from '@lucide/vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const router = useRouter();
const route = useRoute();

const email = ref('');
const password = ref('');
const error = ref('');

async function handleSubmit(): Promise<void> {
    error.value = '';
    try {
        await auth.login(email.value, password.value);
        const redirect = (route.query.redirect as string) || '/dashboard';
        router.push(redirect);
    } catch (e) {
        error.value = auth.getErrorMessage(e);
    }
}
</script>
