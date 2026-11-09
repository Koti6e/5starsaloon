<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\User;
use App\Services\BusinessReportingService;
use App\Support\Money;
use App\Support\ReportingPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Barryvdh\DomPDF\Facade\Pdf;

class SalesReportController extends Controller
{
    public function __invoke(Request $request, BusinessReportingService $reports): View|Response|RedirectResponse
    {
        if ($request->query('export') === 'csv' || $request->query('export') === 'pdf') {
            $exportRequest = $this->validateFilters($request, true);
            return $exportRequest['export'] === 'csv' ? $this->exportCsv($request, $reports) : $this->exportPdf($request, $reports);
        }
        $validated = $this->validateFilters($request);
        $tz = (string) config('app.timezone', 'Asia/Kolkata');
        $reportingPeriod = ReportingPeriod::fromInput($validated, 'monthly', $tz);
        $period = $reportingPeriod->mode;
        $start = $reportingPeriod->start;
        $end = $reportingPeriod->endExclusive;
        $previousPeriod = $reportingPeriod->previous();
        if ($previousPeriod === null) {
            $duration = (int) $start->diffInDays($end);
            $previousPeriod = ReportingPeriod::fromInput([
                'period' => 'custom',
                'start_date' => $start->subDays($duration)->toDateString(),
                'end_date' => $start->subDay()->toDateString(),
            ], timezone: $tz);
        }
        $previousStart = $previousPeriod->start;
        $now = CarbonImmutable::now($tz);
        $summary = $reports->summary($reportingPeriod);
        $previousSummary = $reports->summary($previousPeriod);
        $billers = $reports->completedBills($reportingPeriod)
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
                $row->sales_percentage = $summary['net_sales'] > 0
                    ? ((float) $row->total_sales / $summary['net_sales']) * 100
                    : 0;

                return $row;
            });

        $bills = $reports->completedBills($reportingPeriod)->with(['customer', 'items', 'payments', 'billedBy'])->orderBy('billed_at')->get();
        $daily = [];
        for ($day = $start; $day < $end; $day = $day->addDay()) {
            $dayBills = $bills->filter(fn (Bill $bill) => $bill->billed_at?->timezone($tz)->toDateString() === $day->toDateString());
            $daily[] = ['date' => $day, 'sales' => (float) $dayBills->sum('grand_total'), 'count' => $dayBills->count()];
        }
        $hourly = $period === 'daily' ? $bills->groupBy(fn (Bill $bill) => $bill->billed_at?->timezone($tz)->hour)->map(fn ($rows, $hour) => (object) ['hour' => $hour, 'total' => $rows->sum('grand_total'), 'bill_count' => $rows->count()])->sortKeys()->values() : collect();
        $paymentBreakdown = $summary['collections'];
        return view('admin.reports.sales', [
            'period' => $period, 'reportingPeriod' => $reportingPeriod, 'selectedDate' => $start->toDateString(), 'week' => $start->toDateString(), 'month' => $start->format('Y-m'),
            'periodLabel' => $reportingPeriod->label(),
            'previousValue' => match ($period) { 'daily' => $previousStart->toDateString(), 'weekly' => $previousStart->toDateString(), default => $previousStart->format('Y-m') },
            'nextValue' => match ($period) { 'daily' => $end->toDateString(), 'weekly' => $end->toDateString(), default => $end->format('Y-m') },
            'isCurrent' => $end > $now->startOfDay(), 'dailyRows' => $daily, 'hourlyRows' => $hourly,
            'paymentBreakdown' => $paymentBreakdown, 'bills' => $bills,
            'grossSales' => $summary['gross_sales'], 'totalDiscounts' => $summary['discounts'],
            'totalSales' => $summary['net_sales'],
            'totalBills' => $summary['bill_count'],
            'cancelledBills' => $summary['cancelled_bill_count'],
            'averageBill' => $summary['average_bill'],
            'actualCollections' => $summary['total_collected'],
            'previousSales' => $previousSummary['net_sales'], 'previousBills' => $previousSummary['bill_count'],
            'salesChange' => $previousSummary['net_sales'] > 0 ? (($summary['net_sales'] - $previousSummary['net_sales']) / $previousSummary['net_sales']) * 100 : null,
            'activeStaffCount' => $activeStaff->count(),
            'staffRows' => $staffRows,
            'moneyFormatter' => fn ($amount) => Money::inr($amount),
            'timezone' => $tz,
            'salesAttribution' => 'Bills are attributed to the employee in bills.billed_by. Sales use completed bills and bills.grand_total; per-service performers are stored separately on bill items.',
        ]);
    }

    private function validateFilters(Request $request, bool $includeExport = false): array
    {
        return $request->validate([
            'period' => ['nullable', 'in:daily,weekly,monthly,custom'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'week' => ['nullable', 'date_format:Y-m-d'],
            'month' => ['nullable', 'date_format:Y-m'],
            'start_date' => ['required_if:period,custom', 'nullable', 'date_format:Y-m-d'],
            'end_date' => ['required_if:period,custom', 'nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            ...($includeExport ? ['export' => ['required', 'in:csv,pdf']] : []),
        ]);
    }

    private function exportCsv(Request $request, BusinessReportingService $reports): Response
    {
        $data = $this->exportData($request, $reports);
        return response()->streamDownload(function () use ($data): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            $safe = fn ($value) => preg_match('/^[\x00-\x20]*[=+\-@]/', (string) $value) ? "'".$value : $value;
            fputcsv($out, ['Sales report', ucfirst($data['period']), $data['range']]);
            fputcsv($out, ['Total sales INR', number_format($data['totalSales'], 2, '.', '')]);
            fputcsv($out, ['Completed bills', $data['totalBills']]);
            fputcsv($out, ['Cancelled bills', $data['cancelledBills']]);
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

    private function exportPdf(Request $request, BusinessReportingService $reports)
    {
        $data = $this->exportData($request, $reports);
        return Pdf::loadView('admin.reports.sales-pdf', $data)->download("sales-{$data['period']}-{$data['start']->format('Ymd')}.pdf");
    }

    private function exportData(Request $request, BusinessReportingService $reports): array
    {
        $filters = $this->validateFilters($request);
        $timezone = (string) config('app.timezone', ReportingPeriod::TIMEZONE);
        $reportingPeriod = ReportingPeriod::fromInput($filters, 'monthly', $timezone);
        $period = $reportingPeriod->mode;
        $start = $reportingPeriod->start;
        $end = $reportingPeriod->endExclusive;
        $previousPeriod = $reportingPeriod->previous();
        if ($previousPeriod === null) {
            $duration = (int) $start->diffInDays($end);
            $previousPeriod = ReportingPeriod::fromInput(['period' => 'custom', 'start_date' => $start->subDays($duration)->toDateString(), 'end_date' => $start->subDay()->toDateString()], timezone: $timezone);
        }
        $previousSummary = $reports->summary($previousPeriod);
        $summary = $reports->summary($reportingPeriod);
        $bills = $reports->completedBills($reportingPeriod)->with(['customer', 'billedBy', 'items', 'payments'])->orderBy('billed_at')->get();
        $totalSales = $summary['net_sales'];
        $paymentBreakdown = $summary['collections'];
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
            'period' => $period, 'start' => $start, 'timezone' => $timezone, 'range' => $reportingPeriod->label(),
            'bills' => $bills, 'totalSales' => $totalSales, 'totalBills' => $summary['bill_count'],
            'cancelledBills' => $summary['cancelled_bill_count'],
            'grossSales' => $summary['gross_sales'], 'totalDiscounts' => $summary['discounts'],
            'averageBill' => $summary['average_bill'],
            'previousSales' => $previousSummary['net_sales'], 'previousBills' => $previousSummary['bill_count'],
            'salesChange' => $previousSummary['net_sales'] > 0 ? (($totalSales - $previousSummary['net_sales']) / $previousSummary['net_sales']) * 100 : null,
            'actualCollections' => $summary['total_collected'], 'dailyRows' => $dailyRows,
            'paymentBreakdown' => $paymentBreakdown, 'staffRows' => $staffRows,
        ];
    }
}
