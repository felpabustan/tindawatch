<?php

namespace App\Http\Controllers;

use App\Concerns\InteractsWithStore;
use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Contracts\Repositories\SaleRepositoryInterface;
use App\Contracts\Repositories\StoreRepositoryInterface;
use App\Enums\PaymentMethod;
use App\Models\Sale;
use App\Services\RecordSale;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SaleController extends Controller
{
    use InteractsWithStore;

    public function __construct(
        private SaleRepositoryInterface $sales,
        private ProductRepositoryInterface $products,
        private CustomerRepositoryInterface $customers,
        private StoreRepositoryInterface $stores,
        private RecordSale $recordSale,
    ) {}

    public function index(Request $request): Response
    {
        $store = $this->currentStore($request);
        $filters = $this->saleFilters($request);

        $summary = $this->sales->summaryForStore($store, $filters);
        $summary['by_payment'] = collect($summary['by_payment'])->map(fn (array $row) => [
            ...$row,
            'label' => $this->paymentLabel(PaymentMethod::from($row['method'])),
        ])->all();

        $sales = $this->sales
            ->paginateForStore($store, $filters)
            ->through(fn (Sale $sale) => [
                'id' => $sale->id,
                'total_amount' => $sale->total_amount,
                'payment_method' => $sale->payment_method->value,
                'payment_label' => $this->paymentLabel($sale->payment_method),
                'payment_reference' => $sale->payment_reference,
                'change_amount' => $sale->change_amount,
                'items_count' => $sale->items_count,
                'user' => $sale->user?->name,
                'customer' => $sale->customer?->name,
                'created_at' => $sale->created_at?->timezone(config('app.timezone'))->toIso8601String(),
                'date' => $sale->created_at?->timezone(config('app.timezone'))->format('M j, Y'),
                'time' => $sale->created_at?->timezone(config('app.timezone'))->format('g:i A'),
            ]);

        return Inertia::render('Sales/Index', [
            'sales' => $sales,
            'summary' => $summary,
            'filters' => $filters,
            'staff' => $this->stores->staffForStore($store)->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
            ])->values()->all(),
            'paymentMethods' => collect(PaymentMethod::cases())->map(fn (PaymentMethod $method) => [
                'value' => $method->value,
                'label' => $this->paymentLabel($method),
            ])->values()->all(),
        ]);
    }

    public function create(Request $request): Response
    {
        $store = $this->currentStore($request);

        return Inertia::render('Sales/Pos', [
            'products' => $this->products->inStockForStore($store),
            'customers' => $this->customers->listForStore($store),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $store = $this->currentStore($request);

        $validated = $request->validate([
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'payment_reference' => [
                'nullable',
                'string',
                'max:100',
                Rule::requiredIf($request->input('payment_method') === PaymentMethod::Gcash->value),
            ],
            'amount_tendered' => [
                'nullable',
                'numeric',
                'min:0',
                Rule::requiredIf($request->input('payment_method') === PaymentMethod::Cash->value),
            ],
            'customer_id' => ['nullable', 'integer'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $customer = null;
        if (! empty($validated['customer_id'])) {
            $customer = $this->customers->findForStoreOrFail($store, (int) $validated['customer_id']);
        }

        $amountTendered = null;
        if (PaymentMethod::from($validated['payment_method']) === PaymentMethod::Cash) {
            $amountTendered = Money::toCentavos($validated['amount_tendered']);
        }

        try {
            $this->recordSale->handle(
                $store,
                $request->user(),
                $validated['items'],
                PaymentMethod::from($validated['payment_method']),
                $customer,
                $validated['payment_reference'] ?? null,
                $amountTendered,
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['items' => $e->getMessage()]);
        }

        return to_route('sales.pos');
    }

    /**
     * @return array{from: string, to: string, payment_method: string, q: string, user_id: string}
     */
    private function saleFilters(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'payment_method' => ['nullable', 'string', Rule::enum(PaymentMethod::class)],
            'q' => ['nullable', 'string', 'max:100'],
            'user_id' => ['nullable', 'integer'],
        ]);

        $to = isset($validated['to'])
            ? Carbon::parse($validated['to'])->startOfDay()
            : now()->startOfDay();

        $from = isset($validated['from'])
            ? Carbon::parse($validated['from'])->startOfDay()
            : $to->copy();

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy(), $from->copy()];
        }

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'payment_method' => $validated['payment_method'] ?? '',
            'q' => trim($validated['q'] ?? ''),
            'user_id' => isset($validated['user_id']) ? (string) $validated['user_id'] : '',
        ];
    }

    private function paymentLabel(PaymentMethod $method): string
    {
        return match ($method) {
            PaymentMethod::Cash => 'Cash',
            PaymentMethod::Gcash => 'GCash',
            PaymentMethod::Utang => 'Utang',
        };
    }
}
