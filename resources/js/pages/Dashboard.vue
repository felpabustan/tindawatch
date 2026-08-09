<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowUpRight,
    Banknote,
    ShoppingCart,
    Wallet,
} from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { formatPesos } from '@/lib/money';
import { dashboard } from '@/routes';
import { index as customersIndex } from '@/routes/customers';
import { index as ewalletIndex } from '@/routes/ewallet';
import { index as productsIndex } from '@/routes/products';
import { pos } from '@/routes/sales';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

type DayPoint = {
    date: string;
    label: string;
    total: number;
};

const props = defineProps<{
    stats: {
        sales_today: number;
        sales_count_today: number;
        low_stock_count: number;
        open_utang: number;
        ewallet_count_today: number;
        ewallet_amount_today: number;
        ewallet_fees_today: number;
    };
    salesLast7Days: DayPoint[];
    ewalletLast7Days: DayPoint[];
    recentSales: Array<{
        id: number;
        total_amount: number;
        payment_method: string;
        user: string | null;
        customer: string | null;
        created_at: string | null;
    }>;
    recentEwallet: Array<{
        id: number;
        type: string;
        amount: number;
        service_fee: number;
        provider: string | null;
        processed_by: string | null;
        created_at: string | null;
    }>;
}>();

const salesMax = computed(() =>
    Math.max(...props.salesLast7Days.map((d) => d.total), 1),
);

const ewalletMax = computed(() =>
    Math.max(...props.ewalletLast7Days.map((d) => d.total), 1),
);

function barHeight(total: number, max: number): string {
    return `${Math.max(6, Math.round((total / max) * 100))}%`;
}

const panelClass =
    'rounded-2xl border border-black/5 bg-card p-5 shadow-[0_1px_2px_rgba(0,40,80,0.04),0_10px_28px_rgba(0,86,150,0.07)]';
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-foreground">
                Dashboard
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">Today at a glance</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Link
                :href="pos()"
                :class="panelClass"
                class="group transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_2px_4px_rgba(0,40,80,0.05),0_16px_36px_rgba(0,86,150,0.12)]"
            >
                <div class="flex items-start justify-between gap-3">
                    <p class="text-sm font-medium text-muted-foreground">
                        Sales today
                    </p>
                    <ShoppingCart
                        class="size-4 text-[var(--brand-blue)] opacity-80"
                    />
                </div>
                <p
                    class="mt-4 text-3xl font-semibold tracking-tight text-foreground"
                >
                    {{ formatPesos(stats.sales_today) }}
                </p>
                <p
                    class="mt-2 flex items-center gap-1 text-sm text-muted-foreground"
                >
                    {{ stats.sales_count_today }} sales
                    <ArrowUpRight
                        class="size-3.5 opacity-0 transition group-hover:opacity-100"
                    />
                </p>
            </Link>

            <Link
                :href="ewalletIndex()"
                :class="panelClass"
                class="group transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_2px_4px_rgba(0,40,80,0.05),0_16px_36px_rgba(243,112,33,0.14)]"
            >
                <div class="flex items-start justify-between gap-3">
                    <p class="text-sm font-medium text-muted-foreground">
                        E-wallet today
                    </p>
                    <Wallet
                        class="size-4 text-[var(--brand-orange)] opacity-80"
                    />
                </div>
                <p
                    class="mt-4 text-3xl font-semibold tracking-tight text-foreground"
                >
                    {{ formatPesos(stats.ewallet_amount_today) }}
                </p>
                <p class="mt-2 text-sm text-muted-foreground">
                    {{ stats.ewallet_count_today }} txs ·
                    {{ formatPesos(stats.ewallet_fees_today) }} fees
                </p>
            </Link>

            <Link
                :href="customersIndex()"
                :class="panelClass"
                class="group transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_2px_4px_rgba(0,40,80,0.05),0_16px_36px_rgba(0,86,150,0.12)]"
            >
                <div class="flex items-start justify-between gap-3">
                    <p class="text-sm font-medium text-muted-foreground">
                        Open utang
                    </p>
                    <Banknote
                        class="size-4 text-[var(--brand-blue)] opacity-80"
                    />
                </div>
                <p
                    class="mt-4 text-3xl font-semibold tracking-tight text-foreground"
                >
                    {{ formatPesos(stats.open_utang) }}
                </p>
                <p class="mt-2 text-sm text-muted-foreground">Customers</p>
            </Link>

            <Link
                :href="productsIndex()"
                :class="panelClass"
                class="group transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_2px_4px_rgba(0,40,80,0.05),0_16px_36px_rgba(243,112,33,0.14)]"
            >
                <div class="flex items-start justify-between gap-3">
                    <p class="text-sm font-medium text-muted-foreground">
                        Low stock
                    </p>
                    <AlertTriangle
                        class="size-4 text-[var(--brand-orange)] opacity-80"
                    />
                </div>
                <p
                    class="mt-4 text-3xl font-semibold tracking-tight text-foreground"
                >
                    {{ stats.low_stock_count }}
                </p>
                <p class="mt-2 text-sm text-muted-foreground">Products</p>
            </Link>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <section :class="panelClass">
                <div class="mb-5 flex items-baseline justify-between gap-3">
                    <h2 class="text-base font-semibold text-foreground">
                        Sales
                    </h2>
                    <span class="text-xs text-muted-foreground"
                        >Last 7 days</span
                    >
                </div>
                <div class="flex h-40 items-end gap-2.5">
                    <div
                        v-for="day in salesLast7Days"
                        :key="day.date"
                        class="flex flex-1 flex-col items-center gap-2"
                    >
                        <div class="flex h-28 w-full items-end justify-center">
                            <div
                                class="w-full max-w-9 rounded-full bg-[var(--brand-blue)]/90 transition-all"
                                :style="{
                                    height: barHeight(day.total, salesMax),
                                }"
                                :title="formatPesos(day.total)"
                            />
                        </div>
                        <span class="text-xs text-muted-foreground">{{
                            day.label
                        }}</span>
                    </div>
                </div>
            </section>

            <section :class="panelClass">
                <div class="mb-5 flex items-baseline justify-between gap-3">
                    <h2 class="text-base font-semibold text-foreground">
                        E-wallet
                    </h2>
                    <span class="text-xs text-muted-foreground"
                        >Last 7 days</span
                    >
                </div>
                <div class="flex h-40 items-end gap-2.5">
                    <div
                        v-for="day in ewalletLast7Days"
                        :key="day.date"
                        class="flex flex-1 flex-col items-center gap-2"
                    >
                        <div class="flex h-28 w-full items-end justify-center">
                            <div
                                class="w-full max-w-9 rounded-full bg-[var(--brand-orange)]/90 transition-all"
                                :style="{
                                    height: barHeight(day.total, ewalletMax),
                                }"
                                :title="formatPesos(day.total)"
                            />
                        </div>
                        <span class="text-xs text-muted-foreground">{{
                            day.label
                        }}</span>
                    </div>
                </div>
            </section>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <section :class="panelClass">
                <h2 class="mb-4 text-base font-semibold text-foreground">
                    Recent sales
                </h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-muted-foreground">
                                <th class="pr-2 pb-3 font-medium">Amount</th>
                                <th class="pr-2 pb-3 font-medium">Pay</th>
                                <th class="pr-2 pb-3 font-medium">Staff</th>
                                <th class="pb-3 font-medium">When</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="sale in recentSales"
                                :key="sale.id"
                                class="border-t border-black/5"
                            >
                                <td class="py-3 pr-2 font-medium">
                                    {{ formatPesos(sale.total_amount) }}
                                </td>
                                <td class="py-3 pr-2">
                                    <Badge variant="secondary">{{
                                        sale.payment_method
                                    }}</Badge>
                                </td>
                                <td class="py-3 pr-2">{{ sale.user }}</td>
                                <td class="py-3 text-muted-foreground">
                                    {{ sale.created_at }}
                                </td>
                            </tr>
                            <tr v-if="recentSales.length === 0">
                                <td
                                    colspan="4"
                                    class="py-10 text-center text-muted-foreground"
                                >
                                    No sales yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section :class="panelClass">
                <h2 class="mb-4 text-base font-semibold text-foreground">
                    Recent e-wallet
                </h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-muted-foreground">
                                <th class="pr-2 pb-3 font-medium">Provider</th>
                                <th class="pr-2 pb-3 font-medium">Type</th>
                                <th class="pr-2 pb-3 font-medium">Amount</th>
                                <th class="pb-3 font-medium">By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="tx in recentEwallet"
                                :key="tx.id"
                                class="border-t border-black/5"
                            >
                                <td class="py-3 pr-2">{{ tx.provider }}</td>
                                <td class="py-3 pr-2">
                                    <Badge variant="secondary">{{
                                        tx.type
                                    }}</Badge>
                                </td>
                                <td class="py-3 pr-2 font-medium">
                                    {{ formatPesos(tx.amount) }}
                                </td>
                                <td class="py-3">{{ tx.processed_by }}</td>
                            </tr>
                            <tr v-if="recentEwallet.length === 0">
                                <td
                                    colspan="4"
                                    class="py-10 text-center text-muted-foreground"
                                >
                                    No e-wallet transactions yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</template>
