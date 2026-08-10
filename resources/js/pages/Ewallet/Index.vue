<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ArrowDownToLine, ArrowUpFromLine, Plus } from '@lucide/vue';
import { computed, ref } from 'vue';
import EwalletController from '@/actions/App/Http/Controllers/EwalletController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
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
import { formatPesos } from '@/lib/money';
import { cn } from '@/lib/utils';
import { index as ewalletIndex } from '@/routes/ewallet';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'E-Wallet', href: ewalletIndex() }],
    },
});

type ProviderToday = {
    cash_in: number;
    cash_out: number;
    fees: number;
    count: number;
    net_cash: number;
    net_float: number;
};

type Provider = {
    id: number;
    name: string;
    logo: string | null;
    current_float: number;
    cash_on_hand: number;
    low_float_threshold: number;
    is_low_float: boolean;
    today: ProviderToday;
    today_closed: boolean;
    today_close: {
        fees_settled: number;
        closing_float_after_fees: number;
        closing_cash: number;
        closed_at: string | null;
    } | null;
    close_preview: {
        business_date: string;
        fees_to_settle: number;
        float_after_settle: number;
        can_settle: boolean;
    };
};

const props = defineProps<{
    providers: Provider[];
    availableProviders: Array<{
        name: string;
        logo: string;
    }>;
    transactions: {
        data: Array<{
            id: number;
            type: string;
            amount: number;
            service_fee: number;
            customer_ref: string | null;
            provider: string | null;
            provider_logo: string | null;
            processed_by: string | null;
            created_at: string | null;
        }>;
    };
    canManage: boolean;
}>();

type TxType = 'cash_in' | 'cash_out';

const providerOpen = ref(false);
const selectedProviderName = ref('');
const transactionType = ref<TxType | null>(null);
const selectedProviderId = ref<number | null>(null);
const closeProviderId = ref<number | null>(null);

const transactionOpen = computed({
    get: () => transactionType.value !== null,
    set: (open: boolean) => {
        if (!open) {
            transactionType.value = null;
            selectedProviderId.value = null;
        }
    },
});

const closeOpen = computed({
    get: () => closeProviderId.value !== null,
    set: (open: boolean) => {
        if (!open) {
            closeProviderId.value = null;
        }
    },
});

const transactionTitle = computed(() =>
    transactionType.value === 'cash_out' ? 'Cash-out' : 'Cash-in',
);

const closeProvider = computed(
    () =>
        props.providers.find(
            (provider) => provider.id === closeProviderId.value,
        ) ?? null,
);

const todayTotals = computed(() => {
    return props.providers.reduce(
        (carry, provider) => {
            carry.cash_in += provider.today.cash_in;
            carry.cash_out += provider.today.cash_out;
            carry.fees += provider.today.fees;

            return carry;
        },
        { cash_in: 0, cash_out: 0, fees: 0 },
    );
});

function openTransaction(type: TxType) {
    transactionType.value = type;
    selectedProviderId.value =
        props.providers.find((provider) => !provider.today_closed)?.id ??
        props.providers[0]?.id ??
        null;
}

function openAddProvider() {
    selectedProviderName.value = props.availableProviders[0]?.name ?? '';
    providerOpen.value = true;
}

function openCloseDay(providerId: number) {
    closeProviderId.value = providerId;
}
</script>

<template>
    <Head title="E-Wallet" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <Heading
                title="E-Wallet"
                description="Track GCash/Maya float, cash on hand, and settle fees at day close."
            />
            <div class="flex flex-wrap gap-2">
                <Button
                    variant="outline"
                    :disabled="providers.length === 0"
                    @click="openTransaction('cash_in')"
                >
                    <ArrowDownToLine />
                    Cash-in
                </Button>
                <Button
                    variant="outline"
                    :disabled="providers.length === 0"
                    @click="openTransaction('cash_out')"
                >
                    <ArrowUpFromLine />
                    Cash-out
                </Button>
                <Button
                    v-if="canManage && availableProviders.length > 0"
                    @click="openAddProvider"
                >
                    <Plus />
                    Add provider
                </Button>
            </div>
        </div>

        <div v-if="providers.length > 0" class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-xl border p-4">
                <p class="text-sm text-muted-foreground">Today cash-in</p>
                <p class="mt-1 text-xl font-semibold">
                    {{ formatPesos(todayTotals.cash_in) }}
                </p>
            </div>
            <div class="rounded-xl border p-4">
                <p class="text-sm text-muted-foreground">Today cash-out</p>
                <p class="mt-1 text-xl font-semibold">
                    {{ formatPesos(todayTotals.cash_out) }}
                </p>
            </div>
            <div class="rounded-xl border p-4">
                <p class="text-sm text-muted-foreground">Today fees</p>
                <p class="mt-1 text-xl font-semibold">
                    {{ formatPesos(todayTotals.fees) }}
                </p>
            </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="provider in providers"
                :key="provider.id"
                class="rounded-xl border p-4"
            >
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <img
                            v-if="provider.logo"
                            :src="provider.logo"
                            :alt="provider.name"
                            class="h-8 w-auto max-w-28 object-contain"
                        />
                        <h3 v-else class="font-medium">{{ provider.name }}</h3>
                        <Badge
                            v-if="provider.is_low_float"
                            variant="destructive"
                        >
                            Low float
                        </Badge>
                        <Badge v-if="provider.today_closed" variant="secondary">
                            Closed today
                        </Badge>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div>
                        <p class="text-xs text-muted-foreground">
                            {{ provider.name }} float
                        </p>
                        <p class="mt-1 text-xl font-semibold">
                            {{ formatPesos(provider.current_float) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">
                            Cash on hand
                        </p>
                        <p class="mt-1 text-xl font-semibold">
                            {{ formatPesos(provider.cash_on_hand) }}
                        </p>
                    </div>
                </div>

                <div class="mt-3 space-y-1 text-xs text-muted-foreground">
                    <p>
                        Today: in {{ formatPesos(provider.today.cash_in) }} ·
                        out {{ formatPesos(provider.today.cash_out) }} · fees
                        {{ formatPesos(provider.today.fees) }}
                    </p>
                    <p>
                        Threshold
                        {{ formatPesos(provider.low_float_threshold) }}
                    </p>
                    <p v-if="provider.today_close">
                        Settled fees
                        {{ formatPesos(provider.today_close.fees_settled) }} at
                        {{ provider.today_close.closed_at }}
                    </p>
                </div>

                <Button
                    v-if="!provider.today_closed"
                    class="mt-4 w-full"
                    variant="outline"
                    :disabled="!provider.close_preview.can_settle"
                    @click="openCloseDay(provider.id)"
                >
                    Close day
                </Button>
            </div>
            <div
                v-if="providers.length === 0"
                class="rounded-xl border border-dashed p-6 text-sm text-muted-foreground sm:col-span-2"
            >
                Add GCash or Maya to start tracking float and cash.
            </div>
        </div>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-muted/40 text-left">
                        <th class="px-3 py-2 font-medium">When</th>
                        <th class="px-3 py-2 font-medium">Provider</th>
                        <th class="px-3 py-2 font-medium">Type</th>
                        <th class="px-3 py-2 font-medium">Amount</th>
                        <th class="px-3 py-2 font-medium">Fee</th>
                        <th class="px-3 py-2 font-medium">By</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="tx in transactions.data"
                        :key="tx.id"
                        class="border-b"
                    >
                        <td class="px-3 py-2">{{ tx.created_at }}</td>
                        <td class="px-3 py-2">
                            <div class="flex items-center gap-2">
                                <img
                                    v-if="tx.provider_logo"
                                    :src="tx.provider_logo"
                                    :alt="tx.provider ?? ''"
                                    class="h-5 w-auto max-w-20 object-contain"
                                />
                                <span v-else>{{ tx.provider }}</span>
                            </div>
                        </td>
                        <td class="px-3 py-2">
                            <Badge variant="secondary">{{
                                tx.type === 'cash_in' ? 'Cash-in' : 'Cash-out'
                            }}</Badge>
                        </td>
                        <td class="px-3 py-2">{{ formatPesos(tx.amount) }}</td>
                        <td class="px-3 py-2">
                            {{ formatPesos(tx.service_fee) }}
                        </td>
                        <td class="px-3 py-2">{{ tx.processed_by }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Dialog v-model:open="transactionOpen">
            <DialogContent v-if="transactionType">
                <DialogHeader>
                    <DialogTitle>{{ transactionTitle }}</DialogTitle>
                </DialogHeader>
                <Form
                    v-bind="EwalletController.storeTransaction.form()"
                    class="space-y-4"
                    v-slot="{ errors, processing }"
                    @success="transactionType = null"
                >
                    <input type="hidden" name="type" :value="transactionType" />
                    <input
                        type="hidden"
                        name="provider_id"
                        :value="selectedProviderId ?? ''"
                    />

                    <div class="grid gap-2">
                        <Label>Provider</Label>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                v-for="provider in providers"
                                :key="provider.id"
                                type="button"
                                class="flex h-16 items-center justify-center rounded-lg border px-3 transition"
                                :class="
                                    cn(
                                        selectedProviderId === provider.id
                                            ? 'border-primary bg-primary/5 ring-2 ring-primary/20'
                                            : 'hover:border-primary/40',
                                        provider.today_closed && 'opacity-50',
                                    )
                                "
                                :disabled="provider.today_closed"
                                @click="selectedProviderId = provider.id"
                            >
                                <img
                                    v-if="provider.logo"
                                    :src="provider.logo"
                                    :alt="provider.name"
                                    class="h-7 w-auto max-w-24 object-contain"
                                />
                                <span v-else>{{ provider.name }}</span>
                            </button>
                        </div>
                        <InputError :message="errors.provider_id" />
                    </div>
                    <div class="grid gap-2">
                        <Label>Amount</Label>
                        <Input
                            name="amount"
                            type="number"
                            step="0.01"
                            required
                        />
                        <InputError :message="errors.amount" />
                    </div>
                    <div class="grid gap-2">
                        <Label>Service fee (manual)</Label>
                        <Input
                            name="service_fee"
                            type="number"
                            step="0.01"
                            :default-value="0"
                        />
                        <p class="text-xs text-muted-foreground">
                            Fees vary by customer. Settled from float at day
                            close.
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label>Customer ref</Label>
                        <Input name="customer_ref" />
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="transactionType = null"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            :disabled="processing || !selectedProviderId"
                        >
                            <ArrowDownToLine
                                v-if="transactionType === 'cash_in'"
                            />
                            <ArrowUpFromLine v-else />
                            Save
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="closeOpen">
            <DialogContent v-if="closeProvider">
                <DialogHeader>
                    <DialogTitle>
                        Close {{ closeProvider.name }} day
                    </DialogTitle>
                </DialogHeader>
                <Form
                    v-bind="EwalletController.closeDay.form(closeProvider.id)"
                    class="space-y-4"
                    v-slot="{ errors, processing }"
                    @success="closeProviderId = null"
                >
                    <div class="space-y-2 rounded-lg border p-3 text-sm">
                        <p>
                            Date:
                            <span class="font-medium">{{
                                closeProvider.close_preview.business_date
                            }}</span>
                        </p>
                        <p>
                            Fees to settle from float:
                            <span class="font-medium">{{
                                formatPesos(
                                    closeProvider.close_preview.fees_to_settle,
                                )
                            }}</span>
                        </p>
                        <p>
                            Float after settle:
                            <span class="font-medium">{{
                                formatPesos(
                                    closeProvider.close_preview
                                        .float_after_settle,
                                )
                            }}</span>
                        </p>
                        <p>
                            Cash on hand (unchanged):
                            <span class="font-medium">{{
                                formatPesos(closeProvider.cash_on_hand)
                            }}</span>
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label>Notes (optional)</Label>
                        <Input name="notes" />
                    </div>
                    <InputError :message="errors.close" />
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeProviderId = null"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            :disabled="
                                processing ||
                                !closeProvider.close_preview.can_settle
                            "
                        >
                            Settle fees & close
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="providerOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Add provider</DialogTitle>
                </DialogHeader>
                <Form
                    v-bind="EwalletController.storeProvider.form()"
                    class="space-y-4"
                    v-slot="{ errors, processing }"
                    @success="providerOpen = false"
                >
                    <input
                        type="hidden"
                        name="name"
                        :value="selectedProviderName"
                    />

                    <div class="grid gap-2">
                        <Label>Provider</Label>
                        <div
                            class="grid gap-2"
                            :class="
                                availableProviders.length === 1
                                    ? 'grid-cols-1'
                                    : 'sm:grid-cols-2'
                            "
                        >
                            <button
                                v-for="option in availableProviders"
                                :key="option.name"
                                type="button"
                                class="flex h-16 items-center justify-center rounded-lg border px-3 transition"
                                :class="
                                    cn(
                                        selectedProviderName === option.name
                                            ? 'border-primary bg-primary/5 ring-2 ring-primary/20'
                                            : 'hover:border-primary/40',
                                    )
                                "
                                @click="selectedProviderName = option.name"
                            >
                                <img
                                    :src="option.logo"
                                    :alt="option.name"
                                    class="h-7 w-auto max-w-24 object-contain"
                                />
                            </button>
                        </div>
                        <InputError :message="errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label>Opening float</Label>
                        <Input
                            name="current_float"
                            type="number"
                            step="0.01"
                            required
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label>Opening cash on hand</Label>
                        <Input
                            name="cash_on_hand"
                            type="number"
                            step="0.01"
                            :default-value="0"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label>Low float at</Label>
                        <Input
                            name="low_float_threshold"
                            type="number"
                            step="0.01"
                            required
                        />
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="providerOpen = false"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            :disabled="processing || !selectedProviderName"
                        >
                            <Plus />
                            Add
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    </div>
</template>
