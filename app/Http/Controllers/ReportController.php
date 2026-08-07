<?php

namespace App\Http\Controllers;

use App\Concerns\InteractsWithStore;
use App\Models\Customer;
use App\Services\BuildStoreReports;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    use InteractsWithStore;

    public function __construct(
        private BuildStoreReports $buildStoreReports,
    ) {}

    public function index(Request $request): Response
    {
        $this->ensureReportsAccess($request);
        $store = $this->currentStore($request);

        [$from, $to] = $this->dateRange($request);
        $reports = $this->buildStoreReports->handle($store, $from, $to);

        return Inertia::render('Reports/Index', [
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'section' => $this->section($request),
            ],
            'reports' => $reports,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->ensureReportsAccess($request);
        $store = $this->currentStore($request);

        $validated = $request->validate([
            'type' => ['required', Rule::in(['sales', 'bestsellers', 'inventory', 'utang', 'ewallet'])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        [$from, $to] = $this->dateRange($request);
        $type = $validated['type'];
        $reports = $this->buildStoreReports->handle($store, $from, $to);

        $filename = sprintf(
            'tindawatch-%s-%s-to-%s.csv',
            $type,
            $from->toDateString(),
            $to->toDateString(),
        );

        return response()->streamDownload(function () use ($type, $reports) {
            $out = fopen('php://output', 'w');

            match ($type) {
                'sales' => $this->exportSales($out, $reports['sales']),
                'bestsellers' => $this->exportBestsellers($out, $reports['bestsellers']),
                'inventory' => $this->exportInventory($out, $reports['inventory']),
                'utang' => $this->exportUtang($out, $reports['utang']),
                'ewallet' => $this->exportEwallet($out, $reports['ewallet']),
            };

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportUtangPdf(Request $request): HttpResponse
    {
        $this->ensureReportsAccess($request);
        $store = $this->currentStore($request);

        [$from, $to] = $this->dateRange($request);
        $utang = $this->buildStoreReports->handle($store, $from, $to)['utang'];
        $generatedAt = now()->timezone(config('app.timezone'))->format('M j, Y g:i A');

        $customers = collect($utang['customers'])->map(fn (array $customer) => [
            'name' => $customer['name'],
            'contact' => $customer['contact'],
            'balance_formatted' => Money::format($customer['credit_balance']),
        ])->all();

        $pdf = Pdf::loadView('reports.utang-pdf', [
            'store' => $store,
            'customers' => $customers,
            'totalFormatted' => Money::format($utang['open_balance']),
            'generatedAt' => $generatedAt,
        ])->setPaper('a4');

        $filename = sprintf(
            'utang-%s-%s.pdf',
            str($store->name)->slug()->toString() ?: 'store',
            now()->format('Y-m-d'),
        );

        return $pdf->download($filename);
    }

    public function exportUtangCustomerPdf(Request $request, Customer $customer): HttpResponse
    {
        $this->ensureReportsAccess($request);
        $store = $this->currentStore($request);

        if ((int) $customer->store_id !== (int) $store->id) {
            abort(404);
        }

        $generatedAt = now()->timezone(config('app.timezone'))->format('M j, Y g:i A');

        $pdf = Pdf::loadView('reports.utang-customer-pdf', [
            'store' => $store,
            'customer' => $customer,
            'balanceFormatted' => Money::format($customer->credit_balance),
            'generatedAt' => $generatedAt,
        ])->setPaper('a4');

        $filename = sprintf(
            'utang-%s-%s.pdf',
            str($customer->name)->slug()->toString() ?: 'customer',
            now()->format('Y-m-d'),
        );

        return $pdf->download($filename);
    }

    /**
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    private function dateRange(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'section' => ['nullable', 'string'],
        ]);

        $to = isset($validated['to'])
            ? Carbon::parse($validated['to'])->startOfDay()
            : now()->startOfDay();

        $from = isset($validated['from'])
            ? Carbon::parse($validated['from'])->startOfDay()
            : $to->copy()->subDays(6);

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy(), $from->copy()];
        }

        return [$from, $to];
    }

    private function section(Request $request): string
    {
        $section = $request->string('section')->toString();

        return in_array($section, ['sales', 'bestsellers', 'inventory', 'utang', 'ewallet'], true)
            ? $section
            : 'sales';
    }

    /**
     * @param  resource  $out
     * @param  array{total_amount: int, count: int, by_payment: list<array{method: string, label: string, total: int, count: int}>, daily: list<array{date: string, label: string, total: int, count: int}>}  $sales
     */
    private function exportSales($out, array $sales): void
    {
        fputcsv($out, ['Section', 'Label', 'Count', 'Amount (PHP)']);
        fputcsv($out, ['Summary', 'Total sales', $sales['count'], Money::toPesos($sales['total_amount'])]);

        foreach ($sales['by_payment'] as $row) {
            fputcsv($out, ['Payment', $row['label'], $row['count'], Money::toPesos($row['total'])]);
        }

        foreach ($sales['daily'] as $row) {
            fputcsv($out, ['Daily', $row['date'], $row['count'], Money::toPesos($row['total'])]);
        }
    }

    /**
     * @param  resource  $out
     * @param  list<array{product_id: int, name: string, quantity: int, revenue: int}>  $rows
     */
    private function exportBestsellers($out, array $rows): void
    {
        fputcsv($out, ['Product', 'Quantity', 'Revenue (PHP)']);

        foreach ($rows as $row) {
            fputcsv($out, [$row['name'], $row['quantity'], Money::toPesos($row['revenue'])]);
        }
    }

    /**
     * @param  resource  $out
     * @param  array{products: list<array{id: int, name: string, sku: string|null, stock_qty: int, cost_price: int, sell_price: int, value: int, is_low_stock: bool}>, total_value: int, low_stock_count: int}  $inventory
     */
    private function exportInventory($out, array $inventory): void
    {
        fputcsv($out, ['Product', 'SKU', 'Stock', 'Cost (PHP)', 'Sell (PHP)', 'Value (PHP)', 'Low stock']);

        foreach ($inventory['products'] as $row) {
            fputcsv($out, [
                $row['name'],
                $row['sku'] ?? '',
                $row['stock_qty'],
                Money::toPesos($row['cost_price']),
                Money::toPesos($row['sell_price']),
                Money::toPesos($row['value']),
                $row['is_low_stock'] ? 'Yes' : 'No',
            ]);
        }
    }

    /**
     * @param  resource  $out
     * @param  array{open_balance: int, customers: list<array{id: int, name: string, contact: string|null, credit_balance: int}>, payments: list<array{id: int, customer: string|null, amount: int, paid_at: string|null, received_by: string|null}>, payments_total: int}  $utang
     */
    private function exportUtang($out, array $utang): void
    {
        fputcsv($out, ['Type', 'Name', 'Contact / Received by', 'Amount (PHP)', 'When']);

        foreach ($utang['customers'] as $row) {
            fputcsv($out, [
                'Open balance',
                $row['name'],
                $row['contact'] ?? '',
                Money::toPesos($row['credit_balance']),
                '',
            ]);
        }

        foreach ($utang['payments'] as $row) {
            fputcsv($out, [
                'Payment',
                $row['customer'] ?? '',
                $row['received_by'] ?? '',
                Money::toPesos($row['amount']),
                $row['paid_at'] ?? '',
            ]);
        }
    }

    /**
     * @param  resource  $out
     * @param  array{providers: list<array{id: int, name: string, logo: string|null, current_float: int, is_low_float: bool}>, by_provider: list<array{provider: string, cash_in: int, cash_out: int, fees: int, count: int}>, totals: array{cash_in: int, cash_out: int, fees: int, count: int}}  $ewallet
     */
    private function exportEwallet($out, array $ewallet): void
    {
        fputcsv($out, ['Provider', 'Current float (PHP)', 'Cash-in (PHP)', 'Cash-out (PHP)', 'Fees (PHP)', 'Tx count', 'Low float']);

        $byProvider = collect($ewallet['by_provider'])->keyBy('provider');

        foreach ($ewallet['providers'] as $provider) {
            $stats = $byProvider->get($provider['name'], [
                'cash_in' => 0,
                'cash_out' => 0,
                'fees' => 0,
                'count' => 0,
            ]);

            fputcsv($out, [
                $provider['name'],
                Money::toPesos($provider['current_float']),
                Money::toPesos($stats['cash_in']),
                Money::toPesos($stats['cash_out']),
                Money::toPesos($stats['fees']),
                $stats['count'],
                $provider['is_low_float'] ? 'Yes' : 'No',
            ]);
        }
    }
}
