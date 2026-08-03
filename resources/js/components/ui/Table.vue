<template>
    <div class="w-full overflow-auto rounded-xl border border-border">
        <table class="w-full caption-bottom text-sm">
            <thead class="border-b border-border bg-muted/50">
                <tr>
                    <th
                        v-for="col in columns"
                        :key="col.key"
                        class="h-10 px-4 text-left align-middle font-medium text-muted-foreground"
                        :class="col.class"
                    >
                        {{ col.label }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <slot />
            </tbody>
        </table>
    </div>
</template>

<script setup lang="ts">
export interface TableColumn {
    key: string;
    label: string;
    class?: string;
}

defineProps<{
    columns: TableColumn[];
}>();
</script>
