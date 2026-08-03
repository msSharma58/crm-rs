import { defineStore } from 'pinia';
import { ref, watch } from 'vue';

type Theme = 'light' | 'dark';

export const useThemeStore = defineStore('theme', () => {
    const stored = localStorage.getItem('theme') as Theme | null;
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const theme = ref<Theme>(stored ?? (prefersDark ? 'dark' : 'light'));

    function applyTheme(value: Theme): void {
        document.documentElement.classList.toggle('dark', value === 'dark');
    }

    function setTheme(value: Theme): void {
        theme.value = value;
        localStorage.setItem('theme', value);
        applyTheme(value);
    }

    function toggle(): void {
        setTheme(theme.value === 'dark' ? 'light' : 'dark');
    }

    watch(theme, applyTheme, { immediate: true });

    return { theme, setTheme, toggle };
});
