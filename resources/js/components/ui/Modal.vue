<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="$emit('close')" />
                <div
                    class="relative z-10 w-full max-w-lg rounded-xl border border-border bg-card p-6 shadow-2xl"
                    :class="sizeClass"
                    role="dialog"
                    aria-modal="true"
                >
                    <div v-if="title" class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-foreground">{{ title }}</h2>
                        <button
                            type="button"
                            class="rounded-lg p-1 text-muted-foreground hover:bg-accent hover:text-foreground"
                            @click="$emit('close')"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                    <slot />
                    <div v-if="$slots.footer" class="mt-6 flex justify-end gap-2">
                        <slot name="footer" />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { X } from 'lucide-vue-next';

const props = withDefaults(defineProps<{
    open: boolean;
    title?: string;
    size?: 'sm' | 'md' | 'lg';
}>(), {
    size: 'md',
});

defineEmits<{ close: [] }>();

const sizeClass = computed(() => ({
    sm: 'max-w-sm',
    md: 'max-w-lg',
    lg: 'max-w-2xl',
}[props.size]));
</script>
