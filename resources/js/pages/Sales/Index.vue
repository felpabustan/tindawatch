<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Search, ShoppingCart, X } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatPesos } from '@/lib/money';
import { index as salesIndex, pos } from '@/routes/sales';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Sales', href: salesIndex() }],
    },
});

type SaleRow = {
    id: number;
    total_amount: number;
    payment_method: string;
    payment_label: string;
    payment_reference: string | null;
    change_amount: number | null;
    items_count: number;
    user: string | null;
    customer: string | null;
    created_at: string | null;
    date: string | null;
    time: string | null;
};

type Filters = {
    from: string;
    to: string;
    payment_method: string;
    q: string;
    user_id: string;
};

const props = defineProps<{
    sales: {
        data: SaleRow[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        from: number | null;
        to: number | null;
        total: number;
        current_page: number;
        last_page: number;
    };
    summary: {
        total_amount: number;
        count: number;
        average_amount: number;
        by_payment: Array<{
            method: string;
            label: string;
            count: number;
            total: number;
        }>;
    };
    filters: Filters;
    staff: Array<{ id: number; name: string }>;
    paymentMethods: Array<{ value: string; label: string }>;
}>();

const from = ref(props.filters.from);
const to = ref(props.filters.to);
const paymentMethod = ref(props.filters.payment_method);
const search = ref(props.filters.q);
const userId = ref(props.filters.user_id);

watch(
    () => props.filters,
    (filters) => {
        from.value = filters.from;
        to.value = filters.to;
        paymentMethod.value = filters.payment_method;
        search.value = filters.q;
        userId.value = filters.user_id;
    },
);

const panelClass =
    'rounded-2xl border border-black/5 bg-card p-5 shadow-[0_1px_2px_rgba(0,40,80,0.04),0_10px_28px_rgba(0,86,150,0.07)]';

const selectClass =
    'h-9 w-full rounded-md border border-input bg-background px-2 text-sm shadow-xs';

const hasActiveFilters = computed(
    () =>
        props.filters.payment_method !== '' ||
        props.filters.q !== '' ||
        props.filters.user_id !== '' ||
        props.filters.from !== props.filters.to ||
        props.filters.from !== today(),
);

const rangeLabel = computed(() => {
    if (props.filters.from === props.filters.to) {
        if (props.filters.from === today()) {
            return 'Today';
        }

        if (props.filters.from === daysAgo(1)) {
            return 'Yesterday';
        }

        return formatRangeDate(props.filters.from);
    }

    return `${formatRangeDate(props.filters.from)} – ${formatRangeDate(props.filters.to)}`;
});

function today(): string {
    return formatDateInput(new Date());
}

function daysAgo(n: number): string {
    const date = new Date();
    date.setDate(date.getDate() - n);

    return formatDateInput(date);
}

function startOfMonth(): string {
    const date = new Date();
    date.setDate(1);

    return formatDateInput(date);
}

function formatDateInput(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

function formatRangeDate(value: string): string {
    const date = new Date(`${value}T00:00:00`);

    return date.toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

function filterQuery(overrides: Partial<Filters> = {}) {
    const next = {
        from: from.value,
        to: to.value,
        payment_method: paymentMethod.value,
        q: search.value,
        user_id: userId.value,
        ...overrides,
    };

    return {
        from: next.from,
        to: next.to,
        ...(next.payment_method ? { payment_method: next.payment_method } : {}),
        ...(next.q ? { q: next.q } : {}),
        ...(next.user_id ? { user_id: next.user_id } : {}),
    };
}

function applyFilters(overrides: Partial<Filters> = {}) {
    router.get(salesIndex.url(), filterQuery(overrides), {
        preserveState: true,
        replace: true,
    });
}

function setPreset(preset: 'today' | 'yesterday' | '7days' | 'month') {
    if (preset === 'today') {
        from.value = today();
        to.value = today();
    } else if (preset === 'yesterday') {
        from.value = daysAgo(1);
        to.value = daysAgo(1);
    } else if (preset === '7days') {
        from.value = daysAgo(6);
        to.value = today();
    } else {
        from.value = startOfMonth();
        to.value = today();
    }

    applyFilters({ from: from.value, to: to.value });
}

function clearFilters() {
    from.value = today();
    to.value = today();
    paymentMethod.value = '';
    search.value = '';
    userId.value = '';
    applyFilters({
        from: from.value,
        to: to.value,
        payment_method: '',
        q: '',
        user_id: '',
    });
}

function isPresetActive(preset: 'today' | 'yesterday' | '7days' | 'month'): boolean {
    if (preset === 'today') {
        return props.filters.from === today() && props.filters.to === today();
    }

    if (preset === 'yesterday') {
        return (
            props.filters.from === daysAgo(1) && props.filters.to === daysAgo(1)
        );
    }

    if (preset === '7days') {
        return props.filters.from === daysAgo(6) && props.filters.to === today();
    }

    return props.filters.from === startOfMonth() && props.filters.to === today();
}
</script>

<template>
    <Head title="Sales" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
        >
            <Heading
                title="Sales"
                description="Review takings, find a receipt, and spot how you were paid."
            />
            <Button as-child>
                <Link :href="pos()">
                    <ShoppingCart />
                    Open POS
                </Link>
            </Button>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <div :class="panelClass">
                <p class="text-sm font-medium text-muted-foreground">
                    Total · {{ rangeLabel }}
                </p>
                <p
                    class="mt-3 text-3xl font-semibold tracking-tight text-foreground"
                >
                    {{ formatPesos(summary.total_amount) }}
                </p>
                <p class="mt-2 text-sm text-muted-foreground">
                    {{ summary.count }}
                    {{ summary.count === 1 ? 'sale' : 'sales' }}
                </p>
            </div>

            <div :class="panelClass">
                <p class="text-sm font-medium text-muted-foreground">
                    Average sale
                </p>
                <p
                    class="mt-3 text-3xl font-semibold tracking-tight text-foreground"
                >
                    {{ formatPesos(summary.average_amount) }}
                </p>
                <p class="mt-2 text-sm text-muted-foreground">
                    Across filtered sales
                </p>
            </div>

            <div :class="panelClass">
                <p class="text-sm font-medium text-muted-foreground">
                    By payment
                </p>
                <div class="mt-3 space-y-2.5">
                    <div
                        v-for="row in summary.by_payment"
                        :key="row.method"
                        class="flex items-baseline justify-between gap-3 text-sm"
                    >
                        <span class="text-muted-foreground">
                            {{ row.label }}
                            <span class="text-xs">· {{ row.count }}</span>
                        </span>
                        <span class="font-medium tabular-nums text-foreground">
                            {{ formatPesos(row.total) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <section :class="panelClass" class="space-y-4">
            <div class="flex flex-wrap gap-2">
                <Button
                    type="button"
                    size="sm"
                    :variant="isPresetActive('today') ? 'default' : 'outline'"
                    @click="setPreset('today')"
                >
                    Today
                </Button>
                <Button
                    type="button"
                    size="sm"
                    :variant="
                        isPresetActive('yesterday') ? 'default' : 'outline'
                    "
                    @click="setPreset('yesterday')"
                >
                    Yesterday
                </Button>
                <Button
                    type="button"
                    size="sm"
                    :variant="isPresetActive('7days') ? 'default' : 'outline'"
                    @click="setPreset('7days')"
                >
                    Last 7 days
                </Button>
                <Button
                    type="button"
                    size="sm"
                    :variant="isPresetActive('month') ? 'default' : 'outline'"
                    @click="setPreset('month')"
                >
                    This month
                </Button>
            </div>

            <form
                class="grid gap-3 md:grid-cols-2 xl:grid-cols-6 xl:items-end"
                @submit.prevent="applyFilters()"
            >
                <div class="grid gap-1">
                    <Label for="from">From</Label>
                    <Input id="from" v-model="from" type="date" />
                </div>
                <div class="grid gap-1">
                    <Label for="to">To</Label>
                    <Input id="to" v-model="to" type="date" />
                </div>
                <div class="grid gap-1">
                    <Label for="payment">Payment</Label>
                    <select
                        id="payment"
                        v-model="paymentMethod"
                        :class="selectClass"
                    >
                        <option value="">All methods</option>
                        <option
                            v-for="method in paymentMethods"
                            :key="method.value"
                            :value="method.value"
                        >
                            {{ method.label }}
                        </option>
                    </select>
                </div>
                <div class="grid gap-1">
                    <Label for="staff">Staff</Label>
                    <select id="staff" v-model="userId" :class="selectClass">
                        <option value="">All staff</option>
                        <option
                            v-for="member in staff"
                            :key="member.id"
                            :value="String(member.id)"
                        >
                            {{ member.name }}
                        </option>
                    </select>
                </div>
                <div class="grid gap-1 xl:col-span-1">
                    <Label for="q">Search</Label>
                    <Input
                        id="q"
                        v-model="search"
                        placeholder="Customer, ref, or #ID"
                    />
                </div>
                <div class="flex gap-2">
                    <Button type="submit" class="flex-1">
                        <Search />
                        Apply
                    </Button>
                    <Button
                        v-if="hasActiveFilters"
                        type="button"
                        variant="outline"
                        title="Clear filters"
                        @click="clearFilters"
                    >
                        <X />
                    </Button>
                </div>
            </form>
        </section>

        <section :class="panelClass">
            <div
                class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between"
            >
                <h2 class="text-base font-semibold text-foreground">
                    Transactions
                </h2>
                <p class="text-sm text-muted-foreground">
                    <template v-if="sales.total > 0">
                        Showing {{ sales.from }}–{{ sales.to }} of
                        {{ sales.total }}
                    </template>
                    <template v-else> No matching sales </template>
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-muted-foreground">
                            <th class="pb-3 pr-3 font-medium">Date</th>
                            <th class="pb-3 pr-3 font-medium">Amount</th>
                            <th class="pb-3 pr-3 font-medium">Payment</th>
                            <th class="pb-3 pr-3 font-medium">Items</th>
                            <th class="pb-3 pr-3 font-medium">Customer</th>
                            <th class="pb-3 pr-3 font-medium">Staff</th>
                            <th class="pb-3 font-medium">Ref</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="sale in sales.data"
                            :key="sale.id"
                            class="border-t border-black/5"
                        >
                            <td class="py-3 pr-3">
                                <div class="font-medium text-foreground">
                                    {{ sale.date }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{ sale.time }} · #{{ sale.id }}
                                </div>
                            </td>
                            <td class="py-3 pr-3">
                                <div class="font-medium">
                                    {{ formatPesos(sale.total_amount) }}
                                </div>
                                <div
                                    v-if="sale.change_amount"
                                    class="text-xs text-muted-foreground"
                                >
                                    Change
                                    {{ formatPesos(sale.change_amount) }}
                                </div>
                            </td>
                            <td class="py-3 pr-3">
                                <Badge variant="secondary">{{
                                    sale.payment_label
                                }}</Badge>
                            </td>
                            <td class="py-3 pr-3 text-muted-foreground">
                                {{ sale.items_count }}
                            </td>
                            <td class="py-3 pr-3">
                                {{ sale.customer || 'Walk-in' }}
                            </td>
                            <td class="py-3 pr-3">{{ sale.user || '—' }}</td>
                            <td class="py-3 font-mono text-xs text-muted-foreground">
                                {{ sale.payment_reference || '—' }}
                            </td>
                        </tr>
                        <tr v-if="sales.data.length === 0">
                            <td
                                colspan="7"
                                class="py-12 text-center text-muted-foreground"
                            >
                                No sales for this filter. Try another date or
                                open POS to record one.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="sales.last_page > 1"
                class="mt-5 flex flex-wrap items-center gap-1 border-t border-black/5 pt-4"
            >
                <template v-for="link in sales.links" :key="link.label">
                    <Button
                        v-if="link.url"
                        as-child
                        size="sm"
                        :variant="link.active ? 'default' : 'outline'"
                    >
                        <Link
                            :href="link.url"
                            preserve-scroll
                            v-html="link.label"
                        />
                    </Button>
                    <Button
                        v-else
                        size="sm"
                        variant="outline"
                        disabled
                        v-html="link.label"
                    />
                </template>
            </div>
        </section>
    </div>
</template>
