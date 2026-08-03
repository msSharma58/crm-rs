<template>
    <div class="flex min-h-screen auth-gradient">
        <div class="pointer-events-none fixed inset-0 overflow-hidden">
            <div class="absolute -left-40 -top-40 h-80 w-80 rounded-full bg-brand-500/5 blur-3xl" />
            <div class="absolute -bottom-40 -right-40 h-96 w-96 rounded-full bg-brand-500/5 blur-3xl" />
        </div>
        <div class="relative flex flex-1 items-center justify-center p-6">
            <slot />
        </div>
    </div>
</template>

<script setup lang="ts">
</script>
