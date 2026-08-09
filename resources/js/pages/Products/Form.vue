<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import ProductController from '@/actions/App/Http/Controllers/ProductController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    create as productsCreate,
    index as productsIndex,
} from '@/routes/products';

const props = defineProps<{
    product: {
        id: number;
        name: string;
        sku: string | null;
        category_id: number | null;
        unit: string;
        pieces_per_case: number | null;
        cost_price: number;
        sell_price: number;
        reorder_threshold: number;
        stock_qty: number;
    } | null;
    categories: Array<{ id: number; name: string }>;
}>();

const isEdit = !!props.product;

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Products', href: productsIndex() },
            {
                title: 'Form',
                href: productsCreate(),
            },
        ],
    },
});
</script>

<template>
    <Head :title="isEdit ? 'Edit product' : 'Add product'" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <Heading
            :title="isEdit ? 'Edit product' : 'Add product'"
            description="Prices are in pesos"
        />

        <Form
            v-bind="
                isEdit
                    ? ProductController.update.form(product!.id)
                    : ProductController.store.form()
            "
            class="max-w-xl space-y-4"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    name="name"
                    :default-value="product?.name"
                    required
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="sku">SKU / barcode</Label>
                <Input
                    id="sku"
                    name="sku"
                    :default-value="product?.sku ?? ''"
                />
                <InputError :message="errors.sku" />
            </div>

            <div class="grid gap-2">
                <Label for="category_id">Category</Label>
                <select
                    id="category_id"
                    name="category_id"
                    class="h-9 rounded-md border bg-background px-2 text-sm"
                    :value="product?.category_id ?? ''"
                >
                    <option value="">Uncategorized</option>
                    <option
                        v-for="category in categories"
                        :key="category.id"
                        :value="category.id"
                    >
                        {{ category.name }}
                    </option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="grid gap-2">
                    <Label for="unit">Unit</Label>
                    <select
                        id="unit"
                        name="unit"
                        class="h-9 rounded-md border bg-background px-2 text-sm"
                        :value="product?.unit ?? 'piece'"
                        required
                    >
                        <option value="piece">Piece</option>
                        <option value="sachet">Sachet</option>
                        <option value="case">Case</option>
                        <option value="kilo">Kilo</option>
                    </select>
                </div>
                <div class="grid gap-2">
                    <Label for="pieces_per_case">Pieces per case</Label>
                    <Input
                        id="pieces_per_case"
                        name="pieces_per_case"
                        type="number"
                        :default-value="product?.pieces_per_case ?? ''"
                    />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="grid gap-2">
                    <Label for="cost_price">Cost price</Label>
                    <Input
                        id="cost_price"
                        name="cost_price"
                        type="number"
                        step="0.01"
                        :default-value="product?.cost_price ?? 0"
                        required
                    />
                    <InputError :message="errors.cost_price" />
                </div>
                <div class="grid gap-2">
                    <Label for="sell_price">Sell price</Label>
                    <Input
                        id="sell_price"
                        name="sell_price"
                        type="number"
                        step="0.01"
                        :default-value="product?.sell_price ?? 0"
                        required
                    />
                    <InputError :message="errors.sell_price" />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="grid gap-2">
                    <Label for="reorder_threshold">Low-stock threshold</Label>
                    <Input
                        id="reorder_threshold"
                        name="reorder_threshold"
                        type="number"
                        :default-value="product?.reorder_threshold ?? 5"
                        required
                    />
                </div>
                <div v-if="!isEdit" class="grid gap-2">
                    <Label for="stock_qty">Opening stock</Label>
                    <Input
                        id="stock_qty"
                        name="stock_qty"
                        type="number"
                        :default-value="0"
                    />
                </div>
            </div>

            <Button type="submit" :disabled="processing">
                {{ isEdit ? 'Save changes' : 'Create product' }}
            </Button>
        </Form>
    </div>
</template>
