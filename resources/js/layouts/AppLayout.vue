<template>
    <div class="flex min-h-screen bg-background">
        <!-- Sidebar -->
        <aside class="fixed inset-y-0 left-0 z-30 flex w-64 flex-col border-r border-border bg-sidebar">
            <div class="flex h-16 items-center gap-2 border-b border-border px-6">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-500">
                    <Building2 class="h-4 w-4 text-white" />
                </div>
                <span class="text-xl font-bold tracking-tight text-foreground">Homora</span>
            </div>

            <nav class="flex-1 overflow-y-auto px-3 py-4">
                <div class="relative space-y-0.5">
                    <div
                        class="nav-active-indicator absolute left-0 w-0.5 rounded-full bg-brand-500"
                        :style="indicatorStyle"
                    />
                    <RouterLink
                        v-for="item in navItems"
                        :key="item.to"
                        :ref="(el) => setNavRef(item.to, el as HTMLElement | null)"
                        :to="item.to"
                        class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors"
                        :class="isActive(item.to)
                            ? 'bg-[var(--sidebar-active)] text-sidebar-active-foreground'
                            : 'text-sidebar-foreground hover:bg-accent hover:text-accent-foreground'"
                    >
                        <component :is="item.icon" class="h-4 w-4 shrink-0" />
                        {{ item.label }}
                    </RouterLink>
                </div>
            </nav>

            <div class="border-t border-border p-4">
                <p class="truncate text-xs text-muted-foreground">{{ auth.organization?.name }}</p>
            </div>
        </aside>

        <!-- Main -->
        <div class="flex flex-1 flex-col pl-64">
            <!-- Topbar -->
            <header class="sticky top-0 z-20 flex h-16 items-center gap-4 border-b border-border bg-topbar px-6 glass-topbar">
                <div class="relative flex-1 max-w-md">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <input
                        v-model="searchQuery"
                        type="search"
                        placeholder="Search leads, customers, projects..."
                        class="h-9 w-full rounded-lg border border-border bg-card pl-9 pr-4 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                        @keydown.enter="handleSearch"
                    />
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="relative flex h-9 w-9 items-center justify-center rounded-lg text-muted-foreground hover:bg-accent hover:text-foreground"
                    >
                        <Bell class="h-4 w-4" />
                        <span class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-brand-500" />
                    </button>

                    <button
                        type="button"
                        class="flex h-9 w-9 items-center justify-center rounded-lg text-muted-foreground hover:bg-accent hover:text-foreground"
                        @click="theme.toggle()"
                    >
                        <Sun v-if="theme.theme === 'dark'" class="h-4 w-4" />
                        <Moon v-else class="h-4 w-4" />
                    </button>

                    <Dropdown>
                        <template #trigger>
                            <button class="flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-accent">
                                <Avatar :name="auth.user?.name ?? 'U'" :src="auth.user?.avatar_path" size="sm" />
                                <span class="hidden text-sm font-medium text-foreground sm:block">{{ auth.user?.name }}</span>
                                <ChevronDown class="h-4 w-4 text-muted-foreground" />
                            </button>
                        </template>
                        <button
                            class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm text-foreground hover:bg-accent"
                            @click="router.push('/settings')"
                        >
                            <Settings class="h-4 w-4" />
                            Settings
                        </button>
                        <button
                            class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20"
                            @click="handleLogout"
                        >
                            <LogOut class="h-4 w-4" />
                            Sign out
                        </button>
                    </Dropdown>
                </div>
            </header>

            <main class="flex-1 p-6">
                <RouterView />
            </main>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, nextTick, watch, type Component } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import {
    LayoutDashboard, Users, UserCheck, Building2, MapPin,
    PhoneForwarded, CheckSquare, BookOpen, CreditCard, FileText,
    Megaphone, BarChart3, Settings, Search, Bell, Sun, Moon,
    ChevronDown, LogOut, Kanban,
} from 'lucide-vue-next';
import { useAuthStore } from '@/stores/auth';
import { useThemeStore } from '@/stores/theme';
import Avatar from '@/components/ui/Avatar.vue';
import Dropdown from '@/components/ui/Dropdown.vue';

const auth = useAuthStore();
const theme = useThemeStore();
const route = useRoute();
const router = useRouter();

const searchQuery = ref('');
const navRefs = ref<Record<string, HTMLElement | null>>({});
const indicatorTop = ref(0);
const indicatorHeight = ref(36);

interface NavItem {
    to: string;
    label: string;
    icon: Component;
}

const navItems: NavItem[] = [
    { to: '/dashboard', label: 'Dashboard', icon: LayoutDashboard },
    { to: '/leads', label: 'Leads', icon: Users },
    { to: '/leads/board', label: 'Lead Board', icon: Kanban },
    { to: '/customers', label: 'Customers', icon: UserCheck },
    { to: '/projects', label: 'Projects', icon: Building2 },
    { to: '/visits', label: 'Visits', icon: MapPin },
    { to: '/follow-ups', label: 'Follow-ups', icon: PhoneForwarded },
    { to: '/tasks', label: 'Tasks', icon: CheckSquare },
    { to: '/bookings', label: 'Bookings', icon: BookOpen },
    { to: '/payments', label: 'Payments', icon: CreditCard },
    { to: '/documents', label: 'Documents', icon: FileText },
    { to: '/campaigns', label: 'Campaigns', icon: Megaphone },
    { to: '/reports', label: 'Reports', icon: BarChart3 },
    { to: '/users', label: 'Users', icon: Users },
    { to: '/settings', label: 'Settings', icon: Settings },
];

const indicatorStyle = computed(() => ({
    top: `${indicatorTop.value}px`,
    height: `${indicatorHeight.value}px`,
}));

function setNavRef(to: string, el: HTMLElement | null): void {
    if (el) navRefs.value[to] = el;
}

function isActive(to: string): boolean {
    if (to === '/leads') {
        return route.path === '/leads' || (route.path.startsWith('/leads/') && !route.path.startsWith('/leads/board'));
    }
    return route.path === to || route.path.startsWith(to + '/');
}

function updateIndicator(): void {
    nextTick(() => {
        const active = navItems.find((item) => isActive(item.to));
        if (!active) return;
        const el = navRefs.value[active.to];
        if (!el) return;
        const nav = el.parentElement;
        if (!nav) return;
        const navRect = nav.getBoundingClientRect();
        const elRect = el.getBoundingClientRect();
        indicatorTop.value = elRect.top - navRect.top;
        indicatorHeight.value = elRect.height;
    });
}

function handleSearch(): void {
    if (searchQuery.value.trim()) {
        router.push({ path: '/leads', query: { search: searchQuery.value } });
    }
}

async function handleLogout(): Promise<void> {
    await auth.logout();
    router.push('/login');
}

watch(() => route.path, updateIndicator);
onMounted(updateIndicator);
</script>
