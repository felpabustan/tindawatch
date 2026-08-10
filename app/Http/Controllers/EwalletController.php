<?php

namespace App\Http\Controllers;

use App\Concerns\InteractsWithStore;
use App\Contracts\Repositories\EwalletDayCloseRepositoryInterface;
use App\Contracts\Repositories\EwalletProviderRepositoryInterface;
use App\Contracts\Repositories\EwalletTransactionRepositoryInterface;
use App\Enums\EwalletTransactionType;
use App\Models\EwalletProvider;
use App\Models\EwalletTransaction;
use App\Services\CloseEwalletDay;
use App\Services\RecordEwalletTransaction;
use App\Support\EwalletProviders;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class EwalletController extends Controller
{
    use InteractsWithStore;

    public function __construct(
        private EwalletProviderRepositoryInterface $providers,
        private EwalletTransactionRepositoryInterface $transactions,
        private EwalletDayCloseRepositoryInterface $dayCloses,
        private RecordEwalletTransaction $recordEwalletTransaction,
        private CloseEwalletDay $closeEwalletDay,
    ) {}

    public function index(Request $request): Response
    {
        $store = $this->currentStore($request);
        $todayStart = now()->timezone(config('app.timezone'))->startOfDay();
        $todayEnd = now()->timezone(config('app.timezone'))->endOfDay();
        $todayDate = $todayStart->toDateString();

        $providers = $this->providers
            ->listForStore($store)
            ->sortBy(fn (EwalletProvider $provider) => EwalletProviders::sortOrder($provider->name))
            ->values()
            ->map(function (EwalletProvider $provider) use ($store, $todayStart, $todayEnd, $todayDate) {
                $totals = $this->transactions->totalsForProviderBetween(
                    $store,
                    $provider->id,
                    $todayStart,
                    $todayEnd,
                );
                $close = $this->dayCloses->findForProviderOnDate($provider, $todayStart);

                return [
                    'id' => $provider->id,
                    'name' => $provider->name,
                    'logo' => EwalletProviders::logoFor($provider->name),
                    'current_float' => $provider->current_float,
                    'cash_on_hand' => $provider->cash_on_hand,
                    'low_float_threshold' => $provider->low_float_threshold,
                    'is_low_float' => $provider->isLowFloat(),
                    'today' => [
                        'cash_in' => $totals['cash_in'],
                        'cash_out' => $totals['cash_out'],
                        'fees' => $totals['fees'],
                        'count' => $totals['count'],
                        'net_cash' => $totals['cash_in'] - $totals['cash_out'],
                        'net_float' => $totals['cash_out'] - $totals['cash_in'],
                    ],
                    'today_closed' => $close !== null,
                    'today_close' => $close ? [
                        'fees_settled' => $close->fees_settled,
                        'closing_float_after_fees' => $close->closing_float_after_fees,
                        'closing_cash' => $close->closing_cash,
                        'closed_at' => $close->created_at?->toDateTimeString(),
                    ] : null,
                    'close_preview' => [
                        'business_date' => $todayDate,
                        'fees_to_settle' => $totals['fees'],
                        'float_after_settle' => max(0, $provider->current_float - $totals['fees']),
                        'can_settle' => $provider->current_float >= $totals['fees'],
                    ],
                ];
            });

        $existingNames = $providers->pluck('name')->all();

        $availableProviders = EwalletProviders::catalog()
            ->reject(fn (array $provider) => in_array($provider['name'], $existingNames, true))
            ->map(fn (array $provider) => [
                'name' => $provider['name'],
                'logo' => $provider['logo'],
            ])
            ->values();

        $transactions = $this->transactions
            ->paginateForStore($store)
            ->through(fn (EwalletTransaction $tx) => [
                'id' => $tx->id,
                'type' => $tx->type->value,
                'amount' => $tx->amount,
                'service_fee' => $tx->service_fee,
                'customer_ref' => $tx->customer_ref,
                'provider' => $tx->provider?->name,
                'provider_logo' => EwalletProviders::logoFor($tx->provider?->name),
                'processed_by' => $tx->processor?->name,
                'created_at' => $tx->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('Ewallet/Index', [
            'providers' => $providers,
            'availableProviders' => $availableProviders,
            'transactions' => $transactions,
            'canManage' => $request->user()->canManageCatalog($store),
        ]);
    }

    public function storeProvider(Request $request): RedirectResponse
    {
        $this->ensureCatalogAccess($request);
        $store = $this->currentStore($request);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                Rule::in(EwalletProviders::names()),
                Rule::unique('ewallet_providers', 'name')->where(
                    fn ($query) => $query->where('store_id', $store->id),
                ),
            ],
            'current_float' => ['required', 'numeric', 'min:0'],
            'cash_on_hand' => ['nullable', 'numeric', 'min:0'],
            'low_float_threshold' => ['required', 'numeric', 'min:0'],
        ]);

        $this->providers->create([
            'store_id' => $store->id,
            'name' => $validated['name'],
            'current_float' => Money::toCentavos($validated['current_float']),
            'cash_on_hand' => Money::toCentavos($validated['cash_on_hand'] ?? 0),
            'low_float_threshold' => Money::toCentavos($validated['low_float_threshold']),
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "{$validated['name']} added.",
        ]);

        return back();
    }

    public function storeTransaction(Request $request): RedirectResponse
    {
        $store = $this->currentStore($request);

        $validated = $request->validate([
            'provider_id' => ['required', 'integer'],
            'type' => ['required', Rule::enum(EwalletTransactionType::class)],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'service_fee' => ['nullable', 'numeric', 'min:0'],
            'customer_ref' => ['nullable', 'string', 'max:255'],
        ]);

        $provider = $this->providers->findForStoreOrFail($store, (int) $validated['provider_id']);

        $todayClose = $this->dayCloses->findForProviderOnDate(
            $provider,
            now()->timezone(config('app.timezone'))->startOfDay(),
        );

        if ($todayClose !== null) {
            return back()->withErrors([
                'amount' => 'This e-wallet day is already closed. Reopen tomorrow or undo is not available.',
            ]);
        }

        try {
            $this->recordEwalletTransaction->handle(
                $store,
                $provider,
                $request->user(),
                EwalletTransactionType::from($validated['type']),
                Money::toCentavos($validated['amount']),
                Money::toCentavos($validated['service_fee'] ?? 0),
                $validated['customer_ref'] ?? null,
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => EwalletTransactionType::from($validated['type']) === EwalletTransactionType::CashIn
                ? 'Cash-in recorded.'
                : 'Cash-out recorded.',
        ]);

        return back();
    }

    public function closeDay(Request $request, EwalletProvider $provider): RedirectResponse
    {
        $store = $this->currentStore($request);
        $this->providers->findForStoreOrFail($store, $provider->id);

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->closeEwalletDay->handle(
                $store,
                $provider,
                $request->user(),
                Carbon::now(config('app.timezone')),
                $validated['notes'] ?? null,
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['close' => $e->getMessage()]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "{$provider->name} day closed. Fees settled from float.",
        ]);

        return back();
    }
}
