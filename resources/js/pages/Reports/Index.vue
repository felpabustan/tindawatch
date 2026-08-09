<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Download, FileText } from '@lucide/vue';
import { ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatPesos } from '@/lib/money';
import { exportMethod, index as reportsIndex } from '@/routes/reports';
import {
    customerPdf as utangCustomerPdf,
    pdf as utangPdf,
} from '@/routes/reports/utang';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Reports', href: reportsIndex() }],
    },
});

type Section = 'sales' | 'bestsellers' | 'inventory' | 'utang' | 'ewallet';

const props = defineProps<{
    filters: {
        from: string;
        to: string;
        section: Section;
    };
    reports: {
        sales: {
            total_amount: number;
            count: number;
            by_payment: Array<{
                method: string;
                label: string;
                total: number;
                count: number;
            }>;
            daily: Array<{
                date: string;
                label: string;
                total: number;
                count: number;
            }>;
        };
        bestsellers: Array<{
            product_id: number;
            name: string;
            quantity: number;
            revenue: number;
        }>;
        inventory: {
            products: Array<{
                id: number;
                name: string;
                sku: string | null;
                stock_qty: number;
                cost_price: number;
                sell_price: number;
                value: number;
                is_low_stock: boolean;
            }>;
            total_value: number;
            low_stock_count: number;
        };
        utang: {
            open_balance: number;
            customers: Array<{
                id: number;
                name: string;
                contact: string | null;
                credit_balance: number;
            }>;
            payments: Array<{
                id: number;
                customer: string | null;
                amount: number;
                paid_at: string | null;
                received_by: string | null;
            }>;
            payments_total: number;
        };
        ewallet: {
            providers: Array<{
                id: number;
                name: string;
                logo: string | null;
                current_float: number;
                is_low_float: boolean;
            }>;
            by_provider: Array<{
                provider: string;
                cash_in: number;
                cash_out: number;
                fees: number;
                count: number;
            }>;
            totals: {
                cash_in: number;
                cash_out: number;
                fees: number;
                count: number;
            };
        };
    };
}>();

const from = ref(props.filters.from);
const to = ref(props.filters.to);
const section = ref<Section>(props.filters.section);

const sections: Array<{ id: Section; label: string }> = [
    { id: 'sales', label: 'Sales' },
    { id: 'bestsellers', label: 'Best sellers' },
    { id: 'inventory', label: 'Inventory' },
    { id: 'utang', label: 'Utang' },
    { id: 'ewallet', label: 'E-Wallet' },
];

watch(
    () => props.filters,
    (filters) => {
        from.value = filters.from;
        to.value = filters.to;
        section.value = filters.section;
    },
);

function applyFilters(nextSection: Section = section.value) {
    section.value = nextSection;

    router.get(
        reportsIndex.url({
            query: {
                from: from.value,
                to: to.value,
                section: nextSection,
            },
        }),
        {},
        { preserveState: true, replace: true },
    );
}

function exportUrl(type: Section): string {
    return exportMethod.url({
        query: {
            type,
            from: from.value,
            to: to.value,
        },
    });
}

function utangPdfUrl(): string {
    return utangPdf.url();
}

function utangCustomerPdfUrl(customerId: number): string {
    return utangCustomerPdf.url(customerId);
}
</script>

<template>
    <Head title="Reports" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div
            class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between"
        >
            <Heading title="Reports" />
            <div class="flex flex-wrap items-end gap-3">
                <div class="grid gap-1">
                    <Label for="from">From</Label>
                    <Input id="from" v-model="from" type="date" class="w-40" />
                </div>
                <div class="grid gap-1">
                    <Label for="to">To</Label>
                    <Input id="to" v-model="to" type="date" class="w-40" />
                </div>
                <Button type="button" @click="applyFilters()">Apply</Button>
                <Button v-if="section === 'utang'" variant="outline" as-child>
                    <a :href="utangPdfUrl()">
                        <FileText />
                        PDF
                    </a>
                </Button>
                <Button variant="outline" as-child>
                    <a :href="exportUrl(section)">
                        <Download />
                        CSV
                    </a>
                </Button>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            <Button
                v-for="item in sections"
                :key="item.id"
                type="button"
                size="sm"
                :variant="section === item.id ? 'default' : 'outline'"
                @click="applyFilters(item.id)"
            >
                {{ item.label }}
            </Button>
        </div>

        <div v-if="section === 'sales'" class="space-y-4">
            <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-xl border p-4">
                    <div class="text-sm text-muted-foreground">Sales total</div>
                    <div class="mt-1 text-2xl font-semibold">
                        {{ formatPesos(reports.sales.total_amount) }}
                    </div>
                </div>
                <div class="rounded-xl border p-4">
                    <div class="text-sm text-muted-foreground">
                        Transactions
                    </div>
                    <div class="mt-1 text-2xl font-semibold">
                        {{ reports.sales.count }}
                    </div>
                </div>
                <div class="rounded-xl border p-4">
                    <div class="text-sm text-muted-foreground">By payment</div>
                    <div class="mt-2 space-y-1 text-sm">
                        <div
                            v-for="row in reports.sales.by_payment"
                            :key="row.method"
                            class="flex justify-between gap-2"
                        >
                            <span>{{ row.label }}</span>
                            <span class="font-medium">{{
                                formatPesos(row.total)
                            }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto rounded-xl border">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-muted/40 text-left">
                            <th class="px-3 py-2 font-medium">Date</th>
                            <th class="px-3 py-2 font-medium">Sales</th>
                            <th class="px-3 py-2 font-medium">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="day in reports.sales.daily"
                            :key="day.date"
                            class="border-b"
                        >
                            <td class="px-3 py-2">
                                {{ day.label }}
                                <span class="text-muted-foreground"
                                    >({{ day.date }})</span
                                >
                            </td>
                            <td class="px-3 py-2">{{ day.count }}</td>
                            <td class="px-3 py-2">
                                {{ formatPesos(day.total) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-else-if="section === 'bestsellers'" class="space-y-4">
            <div class="overflow-x-auto rounded-xl border">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-muted/40 text-left">
                            <th class="px-3 py-2 font-medium">Product</th>
                            <th class="px-3 py-2 font-medium">Qty sold</th>
                            <th class="px-3 py-2 font-medium">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in reports.bestsellers"
                            :key="row.product_id"
                            class="border-b"
                        >
                            <td class="px-3 py-2 font-medium">
                                {{ row.name }}
                            </td>
                            <td class="px-3 py-2">{{ row.quantity }}</td>
                            <td class="px-3 py-2">
                                {{ formatPesos(row.revenue) }}
                            </td>
                        </tr>
                        <tr v-if="reports.bestsellers.length === 0">
                            <td
                                colspan="3"
                                class="px-3 py-8 text-center text-muted-foreground"
                            >
                                No sales in this range.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-else-if="section === 'inventory'" class="space-y-4">
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-xl border p-4">
                    <div class="text-sm text-muted-foreground">
                        Inventory value
                    </div>
                    <div class="mt-1 text-2xl font-semibold">
                        {{ formatPesos(reports.inventory.total_value) }}
                    </div>
                </div>
                <div class="rounded-xl border p-4">
                    <div class="text-sm text-muted-foreground">Low stock</div>
                    <div class="mt-1 text-2xl font-semibold">
                        {{ reports.inventory.low_stock_count }}
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto rounded-xl border">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-muted/40 text-left">
                            <th class="px-3 py-2 font-medium">Product</th>
                            <th class="px-3 py-2 font-medium">Stock</th>
                            <th class="px-3 py-2 font-medium">Cost</th>
                            <th class="px-3 py-2 font-medium">Value</th>
                            <th class="px-3 py-2 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="product in reports.inventory.products"
                            :key="product.id"
                            class="border-b"
                        >
                            <td class="px-3 py-2">
                                <div class="font-medium">
                                    {{ product.name }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{ product.sku || '—' }}
                                </div>
                            </td>
                            <td class="px-3 py-2">{{ product.stock_qty }}</td>
                            <td class="px-3 py-2">
                                {{ formatPesos(product.cost_price) }}
                            </td>
                            <td class="px-3 py-2">
                                {{ formatPesos(product.value) }}
                            </td>
                            <td class="px-3 py-2">
                                <Badge
                                    v-if="product.is_low_stock"
                                    variant="destructive"
                                    >Low</Badge
                                >
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-else-if="section === 'utang'" class="space-y-4">
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-xl border p-4">
                    <div class="text-sm text-muted-foreground">Open utang</div>
                    <div class="mt-1 text-2xl font-semibold">
                        {{ formatPesos(reports.utang.open_balance) }}
                    </div>
                </div>
                <div class="rounded-xl border p-4">
                    <div class="text-sm text-muted-foreground">
                        Payments in range
                    </div>
                    <div class="mt-1 text-2xl font-semibold">
                        {{ formatPesos(reports.utang.payments_total) }}
                    </div>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <div class="overflow-x-auto rounded-xl border">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-muted/40 text-left">
                                <th class="px-3 py-2 font-medium">Customer</th>
                                <th class="px-3 py-2 font-medium">Balance</th>
                                <th class="px-3 py-2 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="customer in reports.utang.customers"
                                :key="customer.id"
                                class="border-b"
                            >
                                <td class="px-3 py-2">
                                    <div class="font-medium">
                                        {{ customer.name }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ customer.contact || '—' }}
                                    </div>
                                </td>
                                <td class="px-3 py-2">
                                    {{ formatPesos(customer.credit_balance) }}
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <Button
                                        size="icon-sm"
                                        variant="outline"
                                        as-child
                                        title="Download PDF for Messenger"
                                    >
                                        <a
                                            :href="
                                                utangCustomerPdfUrl(customer.id)
                                            "
                                        >
                                            <FileText />
                                        </a>
                                    </Button>
                                </td>
                            </tr>
                            <tr v-if="reports.utang.customers.length === 0">
                                <td
                                    colspan="3"
                                    class="px-3 py-8 text-center text-muted-foreground"
                                >
                                    No open utang.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="overflow-x-auto rounded-xl border">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-muted/40 text-left">
                                <th class="px-3 py-2 font-medium">Payment</th>
                                <th class="px-3 py-2 font-medium">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="payment in reports.utang.payments"
                                :key="payment.id"
                                class="border-b"
                            >
                                <td class="px-3 py-2">
                                    <div class="font-medium">
                                        {{ payment.customer || '—' }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ payment.paid_at }}
                                    </div>
                                </td>
                                <td class="px-3 py-2">
                                    {{ formatPesos(payment.amount) }}
                                </td>
                            </tr>
                            <tr v-if="reports.utang.payments.length === 0">
                                <td
                                    colspan="2"
                                    class="px-3 py-8 text-center text-muted-foreground"
                                >
                                    No payments in this range.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div v-else class="space-y-4">
            <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-xl border p-4">
                    <div class="text-sm text-muted-foreground">Cash-in</div>
                    <div class="mt-1 text-2xl font-semibold">
                        {{ formatPesos(reports.ewallet.totals.cash_in) }}
                    </div>
                </div>
                <div class="rounded-xl border p-4">
                    <div class="text-sm text-muted-foreground">Cash-out</div>
                    <div class="mt-1 text-2xl font-semibold">
                        {{ formatPesos(reports.ewallet.totals.cash_out) }}
                    </div>
                </div>
                <div class="rounded-xl border p-4">
                    <div class="text-sm text-muted-foreground">Fees</div>
                    <div class="mt-1 text-2xl font-semibold">
                        {{ formatPesos(reports.ewallet.totals.fees) }}
                    </div>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div
                    v-for="provider in reports.ewallet.providers"
                    :key="provider.id"
                    class="rounded-xl border p-4"
                >
                    <div class="flex items-center justify-between gap-2">
                        <img
                            v-if="provider.logo"
                            :src="provider.logo"
                            :alt="provider.name"
                            class="h-6 w-auto max-w-24 object-contain"
                        />
                        <span v-else class="font-medium">{{
                            provider.name
                        }}</span>
                        <Badge
                            v-if="provider.is_low_float"
                            variant="destructive"
                            >Low float</Badge
                        >
                    </div>
                    <div class="mt-2 text-xl font-semibold">
                        {{ formatPesos(provider.current_float) }}
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto rounded-xl border">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-muted/40 text-left">
                            <th class="px-3 py-2 font-medium">Provider</th>
                            <th class="px-3 py-2 font-medium">Cash-in</th>
                            <th class="px-3 py-2 font-medium">Cash-out</th>
                            <th class="px-3 py-2 font-medium">Fees</th>
                            <th class="px-3 py-2 font-medium">Tx</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in reports.ewallet.by_provider"
                            :key="row.provider"
                            class="border-b"
                        >
                            <td class="px-3 py-2 font-medium">
                                {{ row.provider }}
                            </td>
                            <td class="px-3 py-2">
                                {{ formatPesos(row.cash_in) }}
                            </td>
                            <td class="px-3 py-2">
                                {{ formatPesos(row.cash_out) }}
                            </td>
                            <td class="px-3 py-2">
                                {{ formatPesos(row.fees) }}
                            </td>
                            <td class="px-3 py-2">{{ row.count }}</td>
                        </tr>
                        <tr v-if="reports.ewallet.by_provider.length === 0">
                            <td
                                colspan="5"
                                class="px-3 py-8 text-center text-muted-foreground"
                            >
                                No e-wallet activity in this range.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
