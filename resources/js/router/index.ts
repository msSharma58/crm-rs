import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/login',
            name: 'login',
            component: () => import('@/modules/auth/LoginPage.vue'),
            meta: { guest: true },
        },
        {
            path: '/',
            component: () => import('@/layouts/AppLayout.vue'),
            meta: { requiresAuth: true },
            children: [
                {
                    path: '',
                    redirect: '/dashboard',
                },
                {
                    path: 'dashboard',
                    name: 'dashboard',
                    component: () => import('@/modules/dashboard/DashboardPage.vue'),
                },
                {
                    path: 'leads',
                    name: 'leads',
                    component: () => import('@/modules/leads/LeadsPage.vue'),
                },
                {
                    path: 'leads/board',
                    name: 'leads-board',
                    component: () => import('@/modules/leads/LeadBoardPage.vue'),
                },
                {
                    path: 'leads/:id',
                    name: 'lead-detail',
                    component: () => import('@/modules/leads/LeadDetailPage.vue'),
                },
                {
                    path: 'customers',
                    name: 'customers',
                    component: () => import('@/modules/customers/CustomersPage.vue'),
                },
                {
                    path: 'customers/:id',
                    name: 'customer-detail',
                    component: () => import('@/modules/customers/CustomerDetailPage.vue'),
                },
                {
                    path: 'projects',
                    name: 'projects',
                    component: () => import('@/modules/projects/ProjectsPage.vue'),
                },
                {
                    path: 'projects/:id',
                    name: 'project-detail',
                    component: () => import('@/modules/projects/ProjectDetailPage.vue'),
                },
                {
                    path: 'visits',
                    name: 'visits',
                    component: () => import('@/modules/visits/VisitsPage.vue'),
                },
                {
                    path: 'follow-ups',
                    name: 'follow-ups',
                    component: () => import('@/modules/followups/FollowUpsPage.vue'),
                },
                {
                    path: 'tasks',
                    name: 'tasks',
                    component: () => import('@/modules/tasks/TasksPage.vue'),
                },
                {
                    path: 'bookings',
                    name: 'bookings',
                    component: () => import('@/modules/bookings/BookingsPage.vue'),
                },
                {
                    path: 'payments',
                    name: 'payments',
                    component: () => import('@/modules/payments/PaymentsPage.vue'),
                },
                {
                    path: 'documents',
                    name: 'documents',
                    component: () => import('@/modules/documents/DocumentsPage.vue'),
                },
                {
                    path: 'campaigns',
                    name: 'campaigns',
                    component: () => import('@/modules/campaigns/CampaignsPage.vue'),
                },
                {
                    path: 'reports',
                    name: 'reports',
                    component: () => import('@/modules/reports/ReportsPage.vue'),
                },
                {
                    path: 'settings',
                    name: 'settings',
                    component: () => import('@/modules/settings/SettingsPage.vue'),
                },
                {
                    path: 'users',
                    name: 'users',
                    component: () => import('@/modules/users/UsersPage.vue'),
                },
            ],
        },
    ],
    scrollBehavior() {
        return { top: 0 };
    },
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();

    if (!auth.initialized) {
        await auth.fetchMe();
    }

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }

    if (to.meta.guest && auth.isAuthenticated) {
        return { name: 'dashboard' };
    }

    return true;
});

export default router;
