<?php

namespace App\Http\Controllers;

use App\Concerns\InteractsWithStore;
use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Models\CreditPayment;
use App\Models\Customer;
use App\Models\Sale;
use App\Services\RecordCreditPayment;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    use InteractsWithStore;

    public function __construct(
        private CustomerRepositoryInterface $customers,
        private RecordCreditPayment $recordCreditPayment,
    ) {}

    public function index(Request $request): Response
    {
        $store = $this->currentStore($request);
        $search = $request->string('q')->toString();

        $customers = $this->customers
            ->paginateForStore($store, $search !== '' ? $search : null)
            ->through(fn (Customer $customer) => [
                'id' => $customer->id,
                'name' => $customer->name,
                'contact' => $customer->contact,
                'credit_balance' => $customer->credit_balance,
            ]);

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'filters' => ['q' => $search],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $store = $this->currentStore($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact' => ['nullable', 'string', 'max:255'],
        ]);

        $this->customers->create([
            'store_id' => $store->id,
            ...$validated,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Customer added.']);

        return back();
    }

    public function show(Request $request, Customer $customer): Response
    {
        $store = $this->currentStore($request);
        $this->assertCustomerStore($customer, $store->id);

        $customer = $this->customers->loadShowRelations($customer);

        return Inertia::render('Customers/Show', [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'contact' => $customer->contact,
                'credit_balance' => $customer->credit_balance,
                'sales' => $customer->sales->map(fn (Sale $sale) => [
                    'id' => $sale->id,
                    'total_amount' => $sale->total_amount,
                    'payment_method' => $sale->payment_method->value,
                    'created_at' => $sale->created_at?->toDateTimeString(),
                ]),
                'payments' => $customer->creditPayments->map(fn (CreditPayment $payment) => [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'paid_at' => $payment->paid_at?->toDateTimeString(),
                ]),
            ],
        ]);
    }

    public function pay(Request $request, Customer $customer): RedirectResponse
    {
        $store = $this->currentStore($request);
        $this->assertCustomerStore($customer, $store->id);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        try {
            $this->recordCreditPayment->handle(
                $store,
                $customer,
                $request->user(),
                Money::toCentavos($validated['amount']),
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Payment recorded.']);

        return back();
    }

    private function assertCustomerStore(Customer $customer, int $storeId): void
    {
        if ($customer->store_id !== $storeId) {
            abort(404);
        }
    }
}
