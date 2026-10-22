<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\User;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Barryvdh\DomPDF\Facade\Pdf;

class SalesReportController extends Controller
{
    public function __invoke(Request $request): View|Response|RedirectResponse
    {
        if ($request->query('export') === 'excel' || $request->query('export') === 'pdf') {
            $exportRequest = $request->validate(['period' => ['nullable', 'in:daily,weekly,monthly'], 'date' => ['nullable', 'date_format:Y-m-d'], 'week' => ['nullable', 'date_format:Y-m-d'], 'month' => ['nullable', 'date_format:Y-m'], 'export' => ['required', 'in:excel,pdf']]);
            return $exportRequest['export'] === 'excel' ? $this->exportCsv($request) : $this->exportPdf($request);
        }
        $validated = $request->validate([
            'period' => ['nullable', 'in:daily,weekly,monthly'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'week' => ['nullable', 'date_format:Y-m-d'],
            'month' => ['nullable', 'date_format:Y-m'],
        ]);
        $tz = (string) config('app.timezone', 'Asia/Kolkata');
        $period = $validated['period'] ?? (isset($validated['date']) ? 'daily' : (isset($validated['week']) ? 'weekly' : 'monthly'));
        $selected = match ($period) {
            'daily' => CarbonImmutable::parse($validated['date'] ?? now($tz)->toDateString(), $tz)->startOfDay(),
            'weekly' => CarbonImmutable::parse($validated['week'] ?? now($tz)->toDateString(), $tz)->startOfWeek(CarbonImmutable::MONDAY),
            default => CarbonImmutable::createFromFormat('!Y-m', $validated['month'] ?? now($tz)->format('Y-m'), $tz),
        };
        $start = $period === 'weekly' ? $selected->startOfWeek(CarbonImmutable::MONDAY) : $selected;
        $end = match ($period) { 'daily' => $start->addDay(), 'weekly' => $start->addWeek(), default => $start->addMonth() };
        $previousStart = match ($period) { 'daily' => $start->subDay(), 'weekly' => $start->subWeek(), default => $start->subMonth() };
        $previousEnd = $start;
        $now = CarbonImmutable::now($tz);
        $billsFor = fn ($from, $to) => Bill::query()
            ->where('bills.status', 'completed')
            ->where('bills.billed_at', '>=', $from->format('Y-m-d H:i:s'))
            ->where('bills.billed_at', '<', $to->format('Y-m-d H:i:s'));
        $summary = $billsFor($start, $end)
            ->selectRaw('COUNT(*) as bill_count, COALESCE(SUM(grand_total), 0) as total_sales')
            ->first();
        $previous = $billsFor($previousStart, $previousEnd)->selectRaw('COUNT(*) as bill_count, COALESCE(SUM(grand_total), 0) as total_sales')->first();
        $billers = $billsFor($start, $end)
            ->join('users', 'users.id', '=', 'bills.billed_by')
            ->select([
                'users.id as staff_id',
                'users.name as staff_name',
                'users.role as role',
            ])
            ->selectRaw('COUNT(bills.id) as bill_count, COALESCE(SUM(bills.grand_total), 0) as total_sales')
            ->groupBy('users.id', 'users.name', 'users.role')
            ->get()
            ->keyBy('staff_id');

        $activeStaff = User::query()
            ->where('role', 'staff')
            ->where('status', 'active')
            ->get(['id', 'name', 'role']);

        $activeStaffIds = $activeStaff->pluck('id')->flip();
        $staffRows = $activeStaff
            ->map(fn (User $staff) => $billers->get($staff->id) ?? (object) [
                'staff_id' => $staff->id,
                'staff_name' => $staff->name,
                'role' => $staff->role,
                'bill_count' => 0,
                'total_sales' => 0,
            ])
            ->concat($billers->reject(fn ($biller) => $activeStaffIds->has($biller->staff_id))->values())
            ->sortBy(fn ($row) => mb_strtolower($row->staff_name))
            ->values()
            ->map(function ($row) use ($summary) {
                $row->sales_percentage = (float) $summary->total_sales > 0
                    ? ((float) $row->total_sales / (float) $summary->total_sales) * 100
                    : 0;

                return $row;
            });

        $bills = $billsFor($start, $end)->with(['customer', 'items', 'payments', 'billedBy'])->orderBy('billed_at')->get();
        $daily = [];
        for ($day = $start; $day < $end; $day = $day->addDay()) {
            $dayBills = $bills->filter(fn (Bill $bill) => $bill->billed_at?->timezone($tz)->toDateString() === $day->toDateString());
            $daily[] = ['date' => $day, 'sales' => (float) $dayBills->sum('grand_total'), 'count' => $dayBills->count()];
        }
        $hourly = $period === 'daily' ? $bills->groupBy(fn (Bill $bill) => $bill->billed_at?->timezone($tz)->hour)->map(fn ($rows, $hour) => (object) ['hour' => $hour, 'total' => $rows->sum('grand_total'), 'bill_count' => $rows->count()])->sortKeys()->values() : collect();
        $paymentBreakdown = DB::table('payments')->join('bills', 'payments.bill_id', '=', 'bills.id')
            ->where('bills.status', 'completed')->where('payments.paid_at', '>=', $start->format('Y-m-d H:i:s'))->where('payments.paid_at', '<', $end->format('Y-m-d H:i:s'))
            ->selectRaw('payments.payment_method, SUM(payments.amount) as total, COUNT(*) as count')->groupBy('payments.payment_method')->orderBy('payments.payment_method')->get();
        return view('admin.reports.sales', [
            'period' => $period, 'selectedDate' => $start->toDateString(), 'week' => $start->toDateString(), 'month' => $start->format('Y-m'),
            'periodLabel' => match ($period) { 'daily' => $start->format('l, d M Y'), 'weekly' => $start->format('d M').' – '.$end->subDay()->format('d M Y'), default => $start->locale(app()->getLocale())->translatedFormat('F Y') },
            'previousValue' => match ($period) { 'daily' => $previousStart->toDateString(), 'weekly' => $previousStart->toDateString(), default => $previousStart->format('Y-m') },
            'nextValue' => match ($period) { 'daily' => $end->toDateString(), 'weekly' => $end->toDateString(), default => $end->format('Y-m') },
            'isCurrent' => $end > $now->startOfDay(), 'dailyRows' => $daily, 'hourlyRows' => $hourly,
            'paymentBreakdown' => $paymentBreakdown, 'bills' => $bills,
            'totalSales' => $summary->total_sales,
            'totalBills' => (int) $summary->bill_count,
            'averageBill' => $summary->bill_count ? (float) $summary->total_sales / (int) $summary->bill_count : 0,
            'actualCollections' => (float) $paymentBreakdown->sum('total'),
            'previousSales' => (float) $previous->total_sales, 'previousBills' => (int) $previous->bill_count,
            'salesChange' => (float) $previous->total_sales > 0 ? (((float) $summary->total_sales - (float) $previous->total_sales) / (float) $previous->total_sales) * 100 : null,
            'activeStaffCount' => $activeStaff->count(),
            'staffRows' => $staffRows,
            'moneyFormatter' => fn ($amount) => Money::inr($amount),
            'timezone' => $tz,
            'salesAttribution' => 'Bills are attributed to the employee in bills.billed_by. Sales use completed bills and bills.grand_total; per-service performers are stored separately on bill items.',
        ]);
    }

    private function range(Request $request): array
    {
        $values = $request->validate(['period' => ['nullable', 'in:daily,weekly,monthly'], 'date' => ['nullable', 'date_format:Y-m-d'], 'week' => ['nullable', 'date_format:Y-m-d'], 'month' => ['nullable', 'date_format:Y-m']]);
        $period = $values['period'] ?? 'monthly'; $tz = (string) config('app.timezone', 'Asia/Kolkata');
        $start = match ($period) { 'daily' => CarbonImmutable::parse($values['date'] ?? now($tz)->toDateString(), $tz)->startOfDay(), 'weekly' => CarbonImmutable::parse($values['week'] ?? now($tz)->toDateString(), $tz)->startOfWeek(CarbonImmutable::MONDAY), default => CarbonImmutable::createFromFormat('!Y-m', $values['month'] ?? now($tz)->format('Y-m'), $tz) };
        return [$period, $start, match ($period) { 'daily' => $start->addDay(), 'weekly' => $start->addWeek(), default => $start->addMonth() }, $tz];
    }

    private function exportCsv(Request $request): Response
    {
        $data = $this->exportData($request);
        return response()->streamDownload(function () use ($data): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            $safe = fn ($value) => preg_match('/^[\x00-\x20]*[=+\-@]/', (string) $value) ? "'".$value : $value;
            fputcsv($out, ['Sales report', ucfirst($data['period']), $data['range']]);
            fputcsv($out, ['Total sales INR', number_format($data['totalSales'], 2, '.', '')]);
            fputcsv($out, ['Completed bills', $data['totalBills']]);
            fputcsv($out, ['Average bill INR', number_format($data['averageBill'], 2, '.', '')]);
            fputcsv($out, ['Previous period sales INR', number_format($data['previousSales'], 2, '.', '')]);
            fputcsv($out, ['Previous period bills', $data['previousBills']]);
            fputcsv($out, ['Sales change percent', $data['salesChange'] === null ? 'No previous sales' : number_format($data['salesChange'], 1, '.', '')]);
            fputcsv($out, ['Actual collections INR', number_format($data['actualCollections'], 2, '.', '')]);
            fputcsv($out, ['Refunds', 'Not separately recorded in the current database schema']);
            fputcsv($out, []);
            fputcsv($out, ['Payment method', 'Collection INR', 'Payment records']);
            foreach ($data['paymentBreakdown'] as $row) fputcsv($out, [$safe($row->payment_method), number_format((float) $row->total, 2, '.', ''), $row->count]);
            fputcsv($out, []);
            fputcsv($out, ['Daily trend', 'Sales INR', 'Completed bills']);
            foreach ($data['dailyRows'] as $row) fputcsv($out, [$row['date']->format('Y-m-d'), number_format($row['sales'], 2, '.', ''), $row['count']]);
            fputcsv($out, []);
            fputcsv($out, ['Staff / biller', 'Bills', 'Sales INR', 'Share percent']);
            foreach ($data['staffRows'] as $row) fputcsv($out, [$safe($row['name']), $row['count'], number_format($row['sales'], 2, '.', ''), number_format($row['share'], 1, '.', '')]);
            fputcsv($out, []);
            fputcsv($out, ['Invoice', 'Date ('.$data['timezone'].')', 'Customer', 'Services', 'Biller', 'Payment', 'Payment status', 'INR']);
            foreach ($data['bills'] as $bill) {
                $paymentText = $bill->payments->map(fn ($payment) => ucfirst($payment->payment_method).' '.number_format((float) $payment->amount, 2, '.', ''))->join(' + ');
                fputcsv($out, [$safe($bill->invoice_number), $bill->billed_at?->timezone($data['timezone'])->format('Y-m-d H:i:s'), $safe($bill->customer?->name), $safe($bill->items->pluck('service_name_snapshot')->filter()->join(', ')), $safe($bill->billedBy?->name), $safe($paymentText), $bill->payment_status, number_format((float) $bill->grand_total, 2, '.', '')]);
            }
            fclose($out);
        }, "sales-{$data['period']}-{$data['start']->format('Ymd')}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function exportPdf(Request $request)
    {
        $data = $this->exportData($request);
        return Pdf::loadView('admin.reports.sales-pdf', $data)->download("sales-{$data['period']}-{$data['start']->format('Ymd')}.pdf");
    }

    private function exportData(Request $request): array
    {
        [$period, $start, $end, $timezone] = $this->range($request);
        $previousStart = match ($period) { 'daily' => $start->subDay(), 'weekly' => $start->subWeek(), default => $start->subMonth() };
        $previous = Bill::query()->where('status', 'completed')->where('billed_at', '>=', $previousStart->format('Y-m-d H:i:s'))->where('billed_at', '<', $start->format('Y-m-d H:i:s'));
        $previousSales = (float) (clone $previous)->sum('grand_total');
        $previousBills = (int) (clone $previous)->count();
        $bills = Bill::query()->with(['customer', 'billedBy', 'items', 'payments'])->where('status', 'completed')
            ->where('billed_at', '>=', $start->format('Y-m-d H:i:s'))->where('billed_at', '<', $end->format('Y-m-d H:i:s'))->orderBy('billed_at')->get();
        $totalSales = (float) $bills->sum('grand_total');
        $paymentBreakdown = DB::table('payments')->join('bills', 'payments.bill_id', '=', 'bills.id')
            ->where('bills.status', 'completed')->where('payments.paid_at', '>=', $start->format('Y-m-d H:i:s'))->where('payments.paid_at', '<', $end->format('Y-m-d H:i:s'))
            ->selectRaw('payments.payment_method, SUM(payments.amount) as total, COUNT(*) as count')->groupBy('payments.payment_method')->orderBy('payments.payment_method')->get();
        $billers = $bills->groupBy('billed_by')->map(function ($rows) use ($totalSales): array {
            $sales = (float) $rows->sum('grand_total');
            return ['name' => $rows->first()->billedBy?->name ?? 'Unknown biller', 'count' => $rows->count(), 'sales' => $sales, 'share' => $totalSales > 0 ? $sales / $totalSales * 100 : 0];
        });
        $activeStaff = User::query()->where('role', 'staff')->where('status', 'active')->get(['id', 'name']);
        $activeIds = $activeStaff->pluck('id')->flip();
        $staffRows = $activeStaff->map(fn (User $staff) => $billers->get($staff->id) ?? ['name' => $staff->name, 'count' => 0, 'sales' => 0, 'share' => 0])
            ->concat($billers->reject(fn ($row, $id) => $activeIds->has($id))->values())
            ->sortBy(fn ($row) => mb_strtolower($row['name']))->values();
        $dailyRows = [];
        for ($day = $start; $day < $end; $day = $day->addDay()) {
            $dayBills = $bills->filter(fn (Bill $bill) => $bill->billed_at?->timezone($timezone)->toDateString() === $day->toDateString());
            $dailyRows[] = ['date' => $day, 'sales' => (float) $dayBills->sum('grand_total'), 'count' => $dayBills->count()];
        }
        return [
            'period' => $period, 'start' => $start, 'timezone' => $timezone, 'range' => $start->format('d M Y').' – '.$end->subSecond()->format('d M Y'),
            'bills' => $bills, 'totalSales' => $totalSales, 'totalBills' => $bills->count(),
            'averageBill' => $bills->isNotEmpty() ? $totalSales / $bills->count() : 0,
            'previousSales' => $previousSales, 'previousBills' => $previousBills,
            'salesChange' => $previousSales > 0 ? (($totalSales - $previousSales) / $previousSales) * 100 : null,
            'actualCollections' => (float) $paymentBreakdown->sum('total'), 'dailyRows' => $dailyRows,
            'paymentBreakdown' => $paymentBreakdown, 'staffRows' => $staffRows,
        ];
    }
}
