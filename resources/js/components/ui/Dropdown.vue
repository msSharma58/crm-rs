<template>
    <div ref="containerRef" class="relative inline-block">
        <div @click="toggle">
            <slot name="trigger" />
        </div>
        <Teleport to="body">
            <div v-if="open" class="fixed inset-0 z-40" @click="close" />
            <Transition
                enter-active-class="transition duration-100 ease-out"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition duration-75 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div
                    v-if="open"
                    ref="menuRef"
                    class="absolute z-50 min-w-[180px] rounded-lg border border-border bg-card p-1 shadow-lg"
                    :style="menuStyle"
                >
                    <slot />
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';

const open = ref(false);
const containerRef = ref<HTMLElement | null>(null);
const menuRef = ref<HTMLElement | null>(null);

const menuStyle = computed(() => {
    if (!containerRef.value) return {};
    const rect = containerRef.value.getBoundingClientRect();
    return {
        top: `${rect.bottom + 4}px`,
        left: `${rect.right - 180}px`,
    };
});

function toggle(): void {
    open.value = !open.value;
}

function close(): void {
    open.value = false;
}

function onKeydown(e: KeyboardEvent): void {
    if (e.key === 'Escape') close();
}

onMounted(() => document.addEventListener('keydown', onKeydown));
onUnmounted(() => document.removeEventListener('keydown', onKeydown));

defineExpose({ close });
</script>
