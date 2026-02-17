<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Sparkles, UsersRound, LayoutGrid } from 'lucide-vue-next';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import AppLogo from './AppLogo.vue';

const dashboardLink = route('admin.dashboard') as string;
const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboardLink,
        icon: LayoutGrid,
    },
    {
        title: 'Users',
        href: route('admin.users.index') as string,
        icon: UsersRound,
    },
    {
        title: 'Events',
        href: route('admin.events.index') as string,
        icon: Sparkles,
    }
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboardLink">
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
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
