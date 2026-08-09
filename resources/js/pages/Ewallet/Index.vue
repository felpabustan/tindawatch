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

type Provider = {
    id: number;
    name: string;
    logo: string | null;
    current_float: number;
    low_float_threshold: number;
    is_low_float: boolean;
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

const transactionOpen = computed({
    get: () => transactionType.value !== null,
    set: (open: boolean) => {
        if (!open) {
            transactionType.value = null;
            selectedProviderId.value = null;
        }
    },
});

const transactionTitle = computed(() =>
    transactionType.value === 'cash_out' ? 'Cash-out' : 'Cash-in',
);

function openTransaction(type: TxType) {
    transactionType.value = type;
    selectedProviderId.value = props.providers[0]?.id ?? null;
}

function openAddProvider() {
    selectedProviderName.value = props.availableProviders[0]?.name ?? '';
    providerOpen.value = true;
}
</script>

<template>
    <Head title="E-Wallet" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <Heading title="E-Wallet" />
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
                            >Low float</Badge
                        >
                    </div>
                </div>
                <div class="mt-3 text-2xl font-semibold">
                    {{ formatPesos(provider.current_float) }}
                </div>
                <div class="text-xs text-muted-foreground">
                    Threshold {{ formatPesos(provider.low_float_threshold) }}
                </div>
            </div>
            <div
                v-if="providers.length === 0"
                class="rounded-xl border border-dashed p-6 text-sm text-muted-foreground sm:col-span-2"
            >
                Add GCash or Maya to start tracking float.
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
                                    )
                                "
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
                        <Label>Service fee</Label>
                        <Input
                            name="service_fee"
                            type="number"
                            step="0.01"
                            :default-value="0"
                        />
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
                            >Cancel</Button
                        >
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
                            >Cancel</Button
                        >
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
