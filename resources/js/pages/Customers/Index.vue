<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { ChevronRight, Plus, Search } from '@lucide/vue';
import { ref } from 'vue';
import CustomerController from '@/actions/App/Http/Controllers/CustomerController';
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
import { formatPesos } from '@/lib/money';
import { index as customersIndex, show as customersShow } from '@/routes/customers';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Customers', href: customersIndex() }],
    },
});

const props = defineProps<{
    customers: {
        data: Array<{
            id: number;
            name: string;
            contact: string | null;
            credit_balance: number;
        }>;
    };
    filters: { q: string };
}>();

const search = ref(props.filters.q);
const createOpen = ref(false);

function submitSearch() {
    router.get(
        customersIndex.url(),
        { q: search.value },
        { preserveState: true },
    );
}
</script>

<template>
    <Head title="Customers" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <Heading title="Customers" />
            <Button @click="createOpen = true">
                <Plus />
                Add customer
            </Button>
        </div>

        <form class="flex max-w-md gap-2" @submit.prevent="submitSearch">
            <Input v-model="search" placeholder="Search customers" />
            <Button type="submit" variant="outline" size="icon" title="Search">
                <Search />
            </Button>
        </form>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-muted/40 text-left">
                        <th class="px-3 py-2 font-medium">Name</th>
                        <th class="px-3 py-2 font-medium">Contact</th>
                        <th class="px-3 py-2 font-medium">Utang</th>
                        <th class="px-3 py-2 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="customer in customers.data"
                        :key="customer.id"
                        class="border-b"
                    >
                        <td class="px-3 py-2 font-medium">{{ customer.name }}</td>
                        <td class="px-3 py-2">{{ customer.contact || '—' }}</td>
                        <td class="px-3 py-2">
                            {{ formatPesos(customer.credit_balance) }}
                        </td>
                        <td class="px-3 py-2 text-right">
                            <Button
                                size="icon-sm"
                                variant="ghost"
                                as-child
                                title="View"
                            >
                                <Link :href="customersShow(customer.id)">
                                    <ChevronRight />
                                </Link>
                            </Button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Dialog v-model:open="createOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Add customer</DialogTitle>
                </DialogHeader>
                <Form
                    v-bind="CustomerController.store.form()"
                    class="space-y-4"
                    v-slot="{ errors, processing }"
                    @success="createOpen = false"
                >
                    <div class="grid gap-2">
                        <Label for="customer-name">Name</Label>
                        <Input id="customer-name" name="name" required />
                        <InputError :message="errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="customer-contact">Contact</Label>
                        <Input id="customer-contact" name="contact" />
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="createOpen = false"
                            >Cancel</Button
                        >
                        <Button type="submit" :disabled="processing">
                            <Plus />
                            Add
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    </div>
</template>
