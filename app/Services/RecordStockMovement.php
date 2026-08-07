<?php

namespace App\Services;

use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RecordStockMovement
{
    public function __construct(
        private ProductRepositoryInterface $products,
        private ActivityLogger $activityLogger,
    ) {}

    public function handle(
        Store $store,
        Product $product,
        User $user,
        StockMovementType $type,
        int $quantity,
        ?string $reason = null,
    ): StockMovement {
        if ($product->store_id !== $store->id) {
            throw new InvalidArgumentException('Product does not belong to store.');
        }

        if ($quantity === 0) {
            throw new InvalidArgumentException('Quantity cannot be zero.');
        }

        return DB::transaction(function () use ($store, $product, $user, $type, $quantity, $reason) {
            $product = $this->products->findForUpdate($product->id);

            $delta = match ($type) {
                StockMovementType::In => abs($quantity),
                StockMovementType::Out => -abs($quantity),
                StockMovementType::Adjustment => $quantity,
            };

            $newQty = $product->stock_qty + $delta;

            if ($newQty < 0) {
                throw new InvalidArgumentException('Insufficient stock.');
            }

            $this->products->updateStock($product, $newQty);

            $movement = $this->products->createStockMovement(
                $store,
                $product,
                $user,
                $type,
                $quantity,
                $reason,
            );

            $this->activityLogger->log($store, $user, 'stock.'.$type->value, $movement, [
                'product_id' => $product->id,
                'quantity' => $movement->quantity,
                'stock_qty' => $newQty,
            ]);

            return $movement;
        });
    }
}
