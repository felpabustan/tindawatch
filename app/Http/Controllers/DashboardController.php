<?php

namespace App\Http\Controllers;

use App\Concerns\InteractsWithStore;
use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Contracts\Repositories\EwalletTransactionRepositoryInterface;
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Contracts\Repositories\SaleRepositoryInterface;
use App\Models\EwalletTransaction;
use App\Models\Sale;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use InteractsWithStore;

    public function __construct(
        private SaleRepositoryInterface $sales,
        private ProductRepositoryInterface $products,
        private CustomerRepositoryInterface $customers,
        private EwalletTransactionRepositoryInterface $ewalletTransactions,
    ) {}

    public function __invoke(Request $request): Response
    {
        $store = $this->currentStore($request);
        $today = now()->startOfDay();
        $weekStart = now()->subDays(6)->startOfDay();

        $ewalletToday = $this->ewalletTransactions->totalsSince($store, $today);

        return Inertia::render('Dashboard', [
            'stats' => [
                'sales_today' => $this->sales->sumSince($store, $today),
                'sales_count_today' => $this->sales->countSince($store, $today),
                'low_stock_count' => $this->products->countLowStock($store),
                'open_utang' => $this->customers->sumOpenUtang($store),
                'ewallet_count_today' => (int) ($ewalletToday->count ?? 0),
                'ewallet_amount_today' => (int) ($ewalletToday->amount ?? 0),
                'ewallet_fees_today' => (int) ($ewalletToday->fees ?? 0),
            ],
            'salesLast7Days' => $this->dailyTotals(
                $this->sales->dailyTotalsSince($store, $weekStart),
            ),
            'ewalletLast7Days' => $this->dailyTotals(
                $this->ewalletTransactions->dailyTotalsSince($store, $weekStart),
            ),
            'recentSales' => $this->sales->recentForStore($store)->map(fn (Sale $sale) => [
                'id' => $sale->id,
                'total_amount' => $sale->total_amount,
                'payment_method' => $sale->payment_method->value,
                'user' => $sale->user?->name,
                'customer' => $sale->customer?->name,
                'created_at' => $sale->created_at?->toDateTimeString(),
            ]),
            'recentEwallet' => $this->ewalletTransactions->recentForStore($store)->map(fn (EwalletTransaction $tx) => [
                'id' => $tx->id,
                'type' => $tx->type->value,
                'amount' => $tx->amount,
                'service_fee' => $tx->service_fee,
                'provider' => $tx->provider?->name,
                'processed_by' => $tx->processor?->name,
                'created_at' => $tx->created_at?->toDateTimeString(),
            ]),
        ]);
    }

    /**
     * @param  array<string, int|string>  $totalsByDay
     * @return list<array{date: string, label: string, total: int}>
     */
    private function dailyTotals(array $totalsByDay): array
    {
        $days = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $key = $date->toDateString();
            $days[] = [
                'date' => $key,
                'label' => $date->format('D'),
                'total' => (int) ($totalsByDay[$key] ?? 0),
            ];
        }

        return $days;
    }
}
