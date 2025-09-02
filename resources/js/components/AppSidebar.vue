<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Building2, ChartBarStacked, Folder, LayoutGrid, Banknote, Move3d, AlarmClockPlus, Download } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Accounts',
        href: '/accounts',
        icon: Building2,
    },
    {
        title: 'Categories',
        href: '/transaction-categories',
        icon: ChartBarStacked,
    },
    {
        title: 'Expense/Income Account',
        href: '/expense-income-accounts',
        icon: Banknote,
    },
    {
        title: 'Upcomming Exp./Inc.',
        href: '/upcomming-expense-income',
        icon: AlarmClockPlus,
    },
    {
        title: 'transactions',
        href: '/transactions',
        icon: Move3d,
    },
];

const footerNavItems: NavItem[] = [
    // {
    //     title: 'Github Repo',
    //     href: 'https://github.com/nymul-islam-moon/Accounting',
    //     icon: Folder,
    // },
];

const handleBackupDownload = () => {
    window.location.href = route('backup.download');
};
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <!-- Backup Button -->
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        @click="handleBackupDownload"
                        tooltip="Download Backup"
                        class="hover:bg-blue-50 hover:text-blue-600 dark:hover:bg-blue-900/20 dark:hover:text-blue-400"
                    >
                        <Download class="h-4 w-4" />
                        <span>Download Backup</span>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>

            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
