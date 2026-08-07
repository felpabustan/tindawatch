<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Banknote } from '@lucide/vue';
import { ref } from 'vue';
import CustomerController from '@/actions/App/Http/Controllers/CustomerController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatPesos } from '@/lib/money';
import { index as customersIndex } from '@/routes/customers';

defineProps<{
    customer: {
        id: number;
        name: string;
        contact: string | null;
        credit_balance: number;
        sales: Array<{
            id: number;
            total_amount: number;
            payment_method: string;
            created_at: string | null;
        }>;
        payments: Array<{
            id: number;
            amount: number;
            paid_at: string | null;
        }>;
    };
}>();

const payOpen = ref(false);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Customers', href: customersIndex() },
            { title: 'Customer', href: customersIndex() },
        ],
    },
});
</script>

<template>
    <Head :title="customer.name" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <Heading
                :title="customer.name"
                :description="customer.contact || undefined"
            />
            <Button
                v-if="customer.credit_balance > 0"
                @click="payOpen = true"
            >
                <Banknote />
                Record payment
            </Button>
        </div>

        <div class="rounded-xl border p-4">
            <div class="text-sm text-muted-foreground">Outstanding utang</div>
            <div class="text-3xl font-semibold">
                {{ formatPesos(customer.credit_balance) }}
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-xl border p-4">
                <h3 class="mb-3 font-medium">Recent sales</h3>
                <div
                    v-for="sale in customer.sales"
                    :key="sale.id"
                    class="flex items-center justify-between border-b py-2 text-sm last:border-0"
                >
                    <div>
                        #{{ sale.id }}
                        <Badge variant="secondary" class="ml-2">{{
                            sale.payment_method
                        }}</Badge>
                    </div>
                    <div>{{ formatPesos(sale.total_amount) }}</div>
                </div>
            </div>

            <div class="rounded-xl border p-4">
                <h3 class="mb-3 font-medium">Payments</h3>
                <div
                    v-for="payment in customer.payments"
                    :key="payment.id"
                    class="flex items-center justify-between border-b py-2 text-sm last:border-0"
                >
                    <div>{{ payment.paid_at }}</div>
                    <div>{{ formatPesos(payment.amount) }}</div>
                </div>
            </div>
        </div>

        <Dialog v-model:open="payOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Record payment</DialogTitle>
                    <DialogDescription>
                        Outstanding
                        {{ formatPesos(customer.credit_balance) }}
                    </DialogDescription>
                </DialogHeader>
                <Form
                    v-bind="CustomerController.pay.form(customer.id)"
                    class="space-y-4"
                    v-slot="{ errors, processing }"
                    @success="payOpen = false"
                >
                    <div class="grid gap-2">
                        <Label for="pay-amount">Amount</Label>
                        <Input
                            id="pay-amount"
                            name="amount"
                            type="number"
                            step="0.01"
                            required
                        />
                        <InputError :message="errors.amount" />
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="payOpen = false"
                            >Cancel</Button
                        >
                        <Button type="submit" :disabled="processing">
                            <Banknote />
                            Save
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    </div>
</template>
