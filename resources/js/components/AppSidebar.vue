<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    ChartColumn,
    LayoutGrid,
    Package,
    Receipt,
    ShoppingCart,
    Users,
    Wallet,
    Store,
    UserCog,
} from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as customersIndex } from '@/routes/customers';
import { index as ewalletIndex } from '@/routes/ewallet';
import { index as productsIndex } from '@/routes/products';
import { index as reportsIndex } from '@/routes/reports';
import { index as salesIndex, pos } from '@/routes/sales';
import { index as storesIndex } from '@/routes/stores';
import { index as teamIndex } from '@/routes/team';
import type { NavItem } from '@/types';

const page = usePage();
const role = computed(() => page.props.currentStore?.role);

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
        { title: 'POS', href: pos(), icon: ShoppingCart },
        { title: 'Sales', href: salesIndex(), icon: Receipt },
        { title: 'Products', href: productsIndex(), icon: Package },
        { title: 'Customers', href: customersIndex(), icon: Users },
        { title: 'E-Wallet', href: ewalletIndex(), icon: Wallet },
    ];

    if (role.value === 'owner' || role.value === 'manager') {
        items.push({ title: 'Reports', href: reportsIndex(), icon: ChartColumn });
        items.push({ title: 'Team', href: teamIndex(), icon: UserCog });
    }

    items.push({ title: 'Stores', href: storesIndex(), icon: Store });

    return items;
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader class="gap-2">
            <Link
                :href="dashboard()"
                class="flex items-center px-2 py-1.5 group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:px-0"
            >
                <AppLogo />
            </Link>
            <p
                v-if="page.props.currentStore"
                class="truncate px-2 text-xs text-muted-foreground group-data-[collapsible=icon]:hidden"
            >
                {{ page.props.currentStore.name }}
            </p>
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
