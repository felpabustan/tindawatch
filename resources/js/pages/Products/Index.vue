<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { Boxes, PackagePlus, Pencil, Plus, Search } from '@lucide/vue';
import { ref } from 'vue';
import ProductController from '@/actions/App/Http/Controllers/ProductController';
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
import { index as productsIndex } from '@/routes/products';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Products', href: productsIndex() }],
    },
});

type ProductRow = {
    id: number;
    name: string;
    sku: string | null;
    unit: string;
    category: string | null;
    category_id: number | null;
    pieces_per_case: number | null;
    cost_price: number;
    sell_price: number;
    cost_price_pesos: number;
    sell_price_pesos: number;
    stock_qty: number;
    reorder_threshold: number;
    is_low_stock: boolean;
};

const props = defineProps<{
    products: {
        data: ProductRow[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    categories: Array<{ id: number; name: string }>;
    filters: { q: string };
    canManage: boolean;
}>();

const search = ref(props.filters.q);
const createOpen = ref(false);
const editProduct = ref<ProductRow | null>(null);
const stockProduct = ref<ProductRow | null>(null);

function submitSearch() {
    router.get(productsIndex.url(), { q: search.value }, { preserveState: true });
}
</script>

<template>
    <Head title="Products" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <Heading title="Products" />
            <Button v-if="canManage" @click="createOpen = true">
                <Plus />
                Add product
            </Button>
        </div>

        <form class="flex max-w-md gap-2" @submit.prevent="submitSearch">
            <Input v-model="search" placeholder="Search name or SKU" />
            <Button type="submit" variant="outline" size="icon" title="Search">
                <Search />
            </Button>
        </form>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-muted/40 text-left">
                        <th class="px-3 py-2 font-medium">Product</th>
                        <th class="px-3 py-2 font-medium">Stock</th>
                        <th class="px-3 py-2 font-medium">Sell</th>
                        <th class="px-3 py-2 font-medium">Cost</th>
                        <th class="px-3 py-2 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="product in products.data"
                        :key="product.id"
                        class="border-b"
                    >
                        <td class="px-3 py-2">
                            <div class="font-medium">{{ product.name }}</div>
                            <div class="text-xs text-muted-foreground">
                                {{ product.sku || '—' }}
                                <template v-if="product.category">
                                    · {{ product.category }}
                                </template>
                            </div>
                        </td>
                        <td class="px-3 py-2">
                            <div class="flex items-center gap-2">
                                {{ product.stock_qty }}
                                <Badge
                                    v-if="product.is_low_stock"
                                    variant="destructive"
                                    >Low</Badge
                                >
                            </div>
                        </td>
                        <td class="px-3 py-2">
                            {{ formatPesos(product.sell_price) }}
                        </td>
                        <td class="px-3 py-2">
                            {{ formatPesos(product.cost_price) }}
                        </td>
                        <td class="px-3 py-2 text-right">
                            <div class="flex justify-end gap-1">
                                <Button
                                    size="icon-sm"
                                    variant="outline"
                                    title="Adjust stock"
                                    @click="stockProduct = product"
                                >
                                    <Boxes />
                                </Button>
                                <Button
                                    v-if="canManage"
                                    size="icon-sm"
                                    variant="ghost"
                                    title="Edit"
                                    @click="editProduct = product"
                                >
                                    <Pencil />
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="products.data.length === 0">
                        <td
                            colspan="5"
                            class="px-3 py-8 text-center text-muted-foreground"
                        >
                            No products yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Dialog v-model:open="createOpen">
            <DialogContent class="sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>Add product</DialogTitle>
                </DialogHeader>
                <Form
                    v-bind="ProductController.store.form()"
                    class="space-y-4"
                    v-slot="{ errors, processing }"
                    @success="createOpen = false"
                >
                    <div class="grid gap-2">
                        <Label for="create-name">Name</Label>
                        <Input id="create-name" name="name" required />
                        <InputError :message="errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="create-sku">SKU / barcode</Label>
                        <Input id="create-sku" name="sku" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="create-category">Category</Label>
                        <select
                            id="create-category"
                            name="category_id"
                            class="h-9 rounded-md border bg-background px-2 text-sm"
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
                            <Label for="create-unit">Unit</Label>
                            <select
                                id="create-unit"
                                name="unit"
                                class="h-9 rounded-md border bg-background px-2 text-sm"
                                required
                            >
                                <option value="piece">Piece</option>
                                <option value="sachet">Sachet</option>
                                <option value="case">Case</option>
                                <option value="kilo">Kilo</option>
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <Label for="create-ppc">Pieces / case</Label>
                            <Input
                                id="create-ppc"
                                name="pieces_per_case"
                                type="number"
                            />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="create-cost">Cost</Label>
                            <Input
                                id="create-cost"
                                name="cost_price"
                                type="number"
                                step="0.01"
                                required
                            />
                            <InputError :message="errors.cost_price" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="create-sell">Sell</Label>
                            <Input
                                id="create-sell"
                                name="sell_price"
                                type="number"
                                step="0.01"
                                required
                            />
                            <InputError :message="errors.sell_price" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="create-threshold">Low-stock at</Label>
                            <Input
                                id="create-threshold"
                                name="reorder_threshold"
                                type="number"
                                :default-value="5"
                                required
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="create-stock">Opening stock</Label>
                            <Input
                                id="create-stock"
                                name="stock_qty"
                                type="number"
                                :default-value="0"
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="createOpen = false"
                            >Cancel</Button
                        >
                        <Button type="submit" :disabled="processing">
                            <PackagePlus />
                            Create
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="!!editProduct"
            @update:open="(open) => !open && (editProduct = null)"
        >
            <DialogContent v-if="editProduct" class="sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>Edit product</DialogTitle>
                </DialogHeader>
                <Form
                    v-bind="ProductController.update.form(editProduct.id)"
                    class="space-y-4"
                    v-slot="{ errors, processing }"
                    @success="editProduct = null"
                >
                    <div class="grid gap-2">
                        <Label for="edit-name">Name</Label>
                        <Input
                            id="edit-name"
                            name="name"
                            :default-value="editProduct.name"
                            required
                        />
                        <InputError :message="errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="edit-sku">SKU / barcode</Label>
                        <Input
                            id="edit-sku"
                            name="sku"
                            :default-value="editProduct.sku ?? ''"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="edit-category">Category</Label>
                        <select
                            id="edit-category"
                            name="category_id"
                            class="h-9 rounded-md border bg-background px-2 text-sm"
                            :value="editProduct.category_id ?? ''"
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
                            <Label for="edit-unit">Unit</Label>
                            <select
                                id="edit-unit"
                                name="unit"
                                class="h-9 rounded-md border bg-background px-2 text-sm"
                                :value="editProduct.unit"
                                required
                            >
                                <option value="piece">Piece</option>
                                <option value="sachet">Sachet</option>
                                <option value="case">Case</option>
                                <option value="kilo">Kilo</option>
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-ppc">Pieces / case</Label>
                            <Input
                                id="edit-ppc"
                                name="pieces_per_case"
                                type="number"
                                :default-value="editProduct.pieces_per_case ?? ''"
                            />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="edit-cost">Cost</Label>
                            <Input
                                id="edit-cost"
                                name="cost_price"
                                type="number"
                                step="0.01"
                                :default-value="editProduct.cost_price_pesos"
                                required
                            />
                            <InputError :message="errors.cost_price" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-sell">Sell</Label>
                            <Input
                                id="edit-sell"
                                name="sell_price"
                                type="number"
                                step="0.01"
                                :default-value="editProduct.sell_price_pesos"
                                required
                            />
                            <InputError :message="errors.sell_price" />
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label for="edit-threshold">Low-stock at</Label>
                        <Input
                            id="edit-threshold"
                            name="reorder_threshold"
                            type="number"
                            :default-value="editProduct.reorder_threshold"
                            required
                        />
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="editProduct = null"
                            >Cancel</Button
                        >
                        <Button type="submit" :disabled="processing">Save</Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="!!stockProduct"
            @update:open="(open) => !open && (stockProduct = null)"
        >
            <DialogContent v-if="stockProduct">
                <DialogHeader>
                    <DialogTitle>Adjust stock</DialogTitle>
                </DialogHeader>
                <p class="text-sm text-muted-foreground">
                    {{ stockProduct.name }} · {{ stockProduct.stock_qty }} on hand
                </p>
                <Form
                    v-bind="ProductController.adjustStock.form(stockProduct.id)"
                    class="space-y-4"
                    v-slot="{ errors, processing }"
                    @success="stockProduct = null"
                >
                    <div class="grid gap-2">
                        <Label for="stock-type">Type</Label>
                        <select
                            id="stock-type"
                            name="type"
                            class="h-9 rounded-md border bg-background px-2 text-sm"
                            required
                        >
                            <option value="in">Stock in</option>
                            <option value="out">Stock out</option>
                            <option value="adjustment">Adjustment (+/-)</option>
                        </select>
                    </div>
                    <div class="grid gap-2">
                        <Label for="stock-qty">Quantity</Label>
                        <Input id="stock-qty" name="quantity" type="number" required />
                        <InputError :message="errors.quantity" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="stock-reason">Reason</Label>
                        <Input id="stock-reason" name="reason" />
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="stockProduct = null"
                            >Cancel</Button
                        >
                        <Button type="submit" :disabled="processing">Save</Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    </div>
</template>
