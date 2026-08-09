<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Banknote,
    Check,
    CircleCheck,
    HandCoins,
    Search,
    ShoppingCart,
    Smartphone,
    Trash2,
} from '@lucide/vue';
import { computed, nextTick, ref } from 'vue';
import SaleController from '@/actions/App/Http/Controllers/SaleController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { showResult } from '@/composables/useResultModal';
import { formatPesos } from '@/lib/money';
import { index as salesIndex, pos } from '@/routes/sales';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Sales', href: salesIndex() },
            { title: 'POS', href: pos() },
        ],
    },
});

const props = defineProps<{
    products: Array<{
        id: number;
        name: string;
        sku: string | null;
        sell_price: number;
        stock_qty: number;
        unit: string;
    }>;
    customers: Array<{
        id: number;
        name: string;
        credit_balance: number;
    }>;
}>();

type CartLine = {
    product_id: number;
    name: string;
    quantity: number;
    sell_price: number;
    stock_qty: number;
};

const query = ref('');
const cart = ref<CartLine[]>([]);
const paymentMethod = ref<'cash' | 'gcash' | 'utang'>('cash');
const customerId = ref<string>('');
const formError = ref<string | null>(null);
const processing = ref(false);
const gcashModalOpen = ref(false);
const gcashReference = ref('');
const gcashError = ref<string | null>(null);
const gcashInput = ref<HTMLInputElement | null>(null);
const cashModalOpen = ref(false);
const cashTendered = ref('');
const cashError = ref<string | null>(null);
const cashInput = ref<HTMLInputElement | null>(null);

const filteredProducts = computed(() => {
    const q = query.value.trim().toLowerCase();

    if (!q) {
        return props.products;
    }

    return props.products.filter(
        (p) =>
            p.name.toLowerCase().includes(q) ||
            (p.sku ?? '').toLowerCase().includes(q),
    );
});

const total = computed(() =>
    cart.value.reduce((sum, line) => sum + line.sell_price * line.quantity, 0),
);

const tenderedCentavos = computed(() => {
    const pesos = Number(cashTendered.value);

    if (!Number.isFinite(pesos) || pesos < 0) {
        return null;
    }

    return Math.round(pesos * 100);
});

const changeCentavos = computed(() => {
    if (tenderedCentavos.value === null) {
        return null;
    }

    return tenderedCentavos.value - total.value;
});

function addToCart(product: (typeof props.products)[number]) {
    const existing = cart.value.find((line) => line.product_id === product.id);

    if (existing) {
        if (existing.quantity < product.stock_qty) {
            existing.quantity += 1;
        }

        return;
    }

    cart.value.push({
        product_id: product.id,
        name: product.name,
        quantity: 1,
        sell_price: product.sell_price,
        stock_qty: product.stock_qty,
    });
}

function updateQty(productId: number, quantity: number) {
    const line = cart.value.find((item) => item.product_id === productId);

    if (!line) {
        return;
    }

    line.quantity = Math.max(1, Math.min(quantity, line.stock_qty));
}

function removeLine(productId: number) {
    cart.value = cart.value.filter((line) => line.product_id !== productId);
}

function resetCart() {
    cart.value = [];
    customerId.value = '';
    paymentMethod.value = 'cash';
    gcashReference.value = '';
    cashTendered.value = '';
    formError.value = null;
}

function checkout() {
    formError.value = null;

    if (cart.value.length === 0) {
        formError.value = 'Add at least one product.';

        return;
    }

    if (paymentMethod.value === 'utang' && !customerId.value) {
        formError.value = 'Select a customer for utang.';

        return;
    }

    if (paymentMethod.value === 'gcash') {
        gcashError.value = null;
        gcashReference.value = '';
        gcashModalOpen.value = true;
        nextTick(() => gcashInput.value?.focus());

        return;
    }

    if (paymentMethod.value === 'cash') {
        cashError.value = null;
        cashTendered.value = '';
        cashModalOpen.value = true;
        nextTick(() => cashInput.value?.focus());

        return;
    }

    submitSale();
}

function confirmGcashSale() {
    const reference = gcashReference.value.trim();

    if (!reference) {
        gcashError.value = 'Enter the GCash reference number.';

        return;
    }

    gcashError.value = null;
    submitSale(reference);
}

function confirmCashSale() {
    if (tenderedCentavos.value === null) {
        cashError.value = 'Enter the cash tendered.';

        return;
    }

    if (tenderedCentavos.value < total.value) {
        cashError.value = 'Cash tendered is less than the total.';

        return;
    }

    cashError.value = null;
    submitSale(null, tenderedCentavos.value);
}

function submitSale(
    paymentReference: string | null = null,
    amountTenderedCentavos: number | null = null,
) {
    processing.value = true;

    const changeForSuccess =
        amountTenderedCentavos !== null
            ? amountTenderedCentavos - total.value
            : null;

    router.post(
        SaleController.store.url(),
        {
            payment_method: paymentMethod.value,
            payment_reference: paymentReference,
            amount_tendered:
                amountTenderedCentavos !== null
                    ? amountTenderedCentavos / 100
                    : null,
            customer_id: customerId.value || null,
            items: cart.value.map((line) => ({
                product_id: line.product_id,
                quantity: line.quantity,
            })),
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                gcashModalOpen.value = false;
                cashModalOpen.value = false;
                resetCart();
                showResult(
                    'success',
                    'Sale Success!',
                    changeForSuccess !== null
                        ? `Change ${formatPesos(changeForSuccess)}`
                        : null,
                );
            },
            onError: (errors) => {
                const message =
                    errors.amount_tendered ||
                    errors.payment_reference ||
                    errors.items ||
                    errors.payment_method ||
                    errors.customer_id;

                if (paymentMethod.value === 'cash' && cashModalOpen.value) {
                    cashError.value = message || 'Failed to process sale';

                    return;
                }

                if (paymentMethod.value === 'gcash' && gcashModalOpen.value) {
                    gcashError.value = message || 'Failed to process sale';

                    return;
                }

                gcashModalOpen.value = false;
                cashModalOpen.value = false;
                showResult(
                    Object.keys(errors).length > 0 ? 'failure' : 'error',
                    Object.keys(errors).length > 0
                        ? 'Failed to process sale'
                        : undefined,
                );
            },
            onCancel: () => {
                gcashModalOpen.value = false;
                cashModalOpen.value = false;
                showResult('error');
            },
            onFinish: () => {
                processing.value = false;
            },
        },
    );
}
</script>

<template>
    <Head title="POS" />

    <div class="flex flex-1 flex-col gap-4 p-4 lg:flex-row">
        <div class="flex-1 space-y-4">
            <Heading title="POS" />
            <div class="relative max-w-md">
                <Search
                    class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="query"
                    class="pl-8"
                    placeholder="Search products..."
                />
            </div>

            <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                <button
                    v-for="product in filteredProducts"
                    :key="product.id"
                    type="button"
                    class="rounded-xl border p-3 text-left transition hover:border-primary"
                    @click="addToCart(product)"
                >
                    <div class="font-medium">{{ product.name }}</div>
                    <div class="text-sm text-muted-foreground">
                        {{ formatPesos(product.sell_price) }} ·
                        {{ product.stock_qty }} {{ product.unit }}
                    </div>
                </button>
            </div>
        </div>

        <aside class="w-full space-y-4 rounded-xl border p-4 lg:w-96">
            <h2 class="flex items-center gap-2 font-semibold">
                <ShoppingCart class="size-4" />
                Cart
            </h2>

            <div v-if="cart.length === 0" class="text-sm text-muted-foreground">
                Empty
            </div>

            <div
                v-for="line in cart"
                :key="line.product_id"
                class="flex items-start justify-between gap-2 border-b py-2"
            >
                <div>
                    <div class="text-sm font-medium">{{ line.name }}</div>
                    <div class="text-xs text-muted-foreground">
                        {{ formatPesos(line.sell_price) }}
                    </div>
                    <div class="mt-1 flex items-center gap-2">
                        <Input
                            type="number"
                            class="h-8 w-20"
                            :model-value="line.quantity"
                            @update:model-value="
                                (v) =>
                                    updateQty(line.product_id, Number(v || 1))
                            "
                        />
                        <Button
                            type="button"
                            size="icon-sm"
                            variant="ghost"
                            title="Remove"
                            @click="removeLine(line.product_id)"
                        >
                            <Trash2 class="text-destructive" />
                        </Button>
                    </div>
                </div>
                <div class="text-sm font-medium">
                    {{ formatPesos(line.sell_price * line.quantity) }}
                </div>
            </div>

            <div class="flex justify-between text-base font-semibold">
                <span>Total</span>
                <span>{{ formatPesos(total) }}</span>
            </div>

            <div class="grid gap-2">
                <Label>Payment</Label>
                <div class="grid grid-cols-3 gap-2">
                    <Button
                        type="button"
                        size="sm"
                        :variant="paymentMethod === 'cash' ? 'default' : 'outline'"
                        @click="paymentMethod = 'cash'"
                    >
                        <Banknote />
                        Cash
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        :variant="paymentMethod === 'gcash' ? 'default' : 'outline'"
                        @click="paymentMethod = 'gcash'"
                    >
                        <Smartphone />
                        GCash
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        :variant="paymentMethod === 'utang' ? 'default' : 'outline'"
                        @click="paymentMethod = 'utang'"
                    >
                        <HandCoins />
                        Utang
                    </Button>
                </div>
            </div>

            <div class="grid gap-2">
                <Label>Customer</Label>
                <select
                    v-model="customerId"
                    class="h-9 rounded-md border bg-background px-2 text-sm"
                >
                    <option value="">Walk-in</option>
                    <option
                        v-for="customer in customers"
                        :key="customer.id"
                        :value="String(customer.id)"
                    >
                        {{ customer.name
                        }}<template v-if="paymentMethod === 'utang'">
                            (utang {{ formatPesos(customer.credit_balance) }})</template
                        >
                    </option>
                </select>
            </div>

            <InputError :message="formError ?? undefined" />

            <Button class="w-full" :disabled="processing" @click="checkout">
                <CircleCheck />
                Complete
            </Button>
        </aside>
    </div>

    <Dialog v-model:open="cashModalOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Cash payment</DialogTitle>
            </DialogHeader>

            <div class="space-y-4">
                <div
                    class="rounded-lg border bg-muted/40 px-4 py-3 text-center"
                >
                    <div class="text-xs text-muted-foreground">Amount due</div>
                    <div class="text-2xl font-semibold">
                        {{ formatPesos(total) }}
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="cash-tendered">Cash tendered</Label>
                    <Input
                        id="cash-tendered"
                        ref="cashInput"
                        v-model="cashTendered"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="0.00"
                        autocomplete="off"
                        @keyup.enter="confirmCashSale"
                    />
                    <InputError :message="cashError ?? undefined" />
                </div>

                <div
                    class="rounded-lg border px-4 py-3 text-center"
                    :class="
                        changeCentavos !== null && changeCentavos >= 0
                            ? 'border-emerald-200 bg-emerald-50'
                            : 'bg-muted/40'
                    "
                >
                    <div class="text-xs text-muted-foreground">Change</div>
                    <div class="text-2xl font-semibold">
                        {{
                            changeCentavos !== null && changeCentavos >= 0
                                ? formatPesos(changeCentavos)
                                : '—'
                        }}
                    </div>
                </div>
            </div>

            <DialogFooter>
                <Button
                    type="button"
                    variant="outline"
                    :disabled="processing"
                    @click="cashModalOpen = false"
                >
                    Cancel
                </Button>
                <Button
                    type="button"
                    :disabled="processing"
                    @click="confirmCashSale"
                >
                    <Check />
                    Confirm
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="gcashModalOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>GCash ref</DialogTitle>
            </DialogHeader>

            <div class="space-y-4">
                <div
                    class="rounded-lg border bg-muted/40 px-4 py-3 text-center"
                >
                    <div class="text-xs text-muted-foreground">Amount due</div>
                    <div class="text-2xl font-semibold">
                        {{ formatPesos(total) }}
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="gcash-ref">Reference number</Label>
                    <Input
                        id="gcash-ref"
                        ref="gcashInput"
                        v-model="gcashReference"
                        placeholder="e.g. 1234 5678 9012"
                        autocomplete="off"
                        @keyup.enter="confirmGcashSale"
                    />
                    <InputError :message="gcashError ?? undefined" />
                </div>
            </div>

            <DialogFooter>
                <Button
                    type="button"
                    variant="outline"
                    :disabled="processing"
                    @click="gcashModalOpen = false"
                >
                    Cancel
                </Button>
                <Button
                    type="button"
                    :disabled="processing"
                    @click="confirmGcashSale"
                >
                    <Check />
                    Confirm
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
