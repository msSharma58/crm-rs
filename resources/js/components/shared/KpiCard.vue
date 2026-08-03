<template>
    <div class="rounded-xl border border-border bg-card p-5 shadow-sm">
        <div class="flex items-start justify-between">
            <div class="space-y-1">
                <p class="text-sm font-medium text-muted-foreground">{{ label }}</p>
                <p
                    class="text-3xl font-semibold tracking-tight text-foreground"
                    v-motion
                    :initial="{ opacity: 0, y: 8 }"
                    :enter="{ opacity: 1, y: 0, transition: { delay: delay * 50 } }"
                >
                    {{ displayValue }}
                    <span v-if="suffix" class="text-lg font-normal text-muted-foreground">{{ suffix }}</span>
                </p>
                <p v-if="change" class="text-xs" :class="change >= 0 ? 'text-emerald-600' : 'text-red-500'">
                    {{ change >= 0 ? '+' : '' }}{{ change }}% from last month
                </p>
            </div>
            <div
                v-if="icon"
                class="flex h-10 w-10 items-center justify-center rounded-lg"
                :class="iconBg"
            >
                <component :is="icon" class="h-5 w-5" :class="iconColor" />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, type Component } from 'vue';

const props = withDefaults(defineProps<{
    label: string;
    value: number;
    suffix?: string;
    change?: number;
    icon?: Component;
    iconBg?: string;
    iconColor?: string;
    delay?: number;
    animate?: boolean;
}>(), {
    iconBg: 'bg-brand-500/10',
    iconColor: 'text-brand-500',
    delay: 0,
    animate: true,
});

const displayValue = ref(props.animate ? 0 : props.value);

function animateCount(target: number): void {
    if (!props.animate) {
        displayValue.value = target;
        return;
    }
    const duration = 800;
    const start = displayValue.value;
    const startTime = performance.now();

    function step(now: number): void {
        const progress = Math.min((now - startTime) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        displayValue.value = Math.round(start + (target - start) * eased);
        if (progress < 1) requestAnimationFrame(step);
    }

    requestAnimationFrame(step);
}

watch(() => props.value, (v) => animateCount(v), { immediate: false });
onMounted(() => animateCount(props.value));
</script>
