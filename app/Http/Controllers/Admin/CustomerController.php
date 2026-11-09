<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Customer;
use App\Support\ReportingPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'period' => ['nullable', 'in:daily,weekly,monthly,custom'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'week' => ['nullable', 'date_format:Y-m-d'],
            'month' => ['nullable', 'date_format:Y-m'],
            'start_date' => ['required_if:period,custom', 'nullable', 'date_format:Y-m-d'],
            'end_date' => ['required_if:period,custom', 'nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'search' => ['nullable', 'string', 'max:100'],
            'filter' => ['nullable', 'string', 'max:40'],
        ]);
        $periodSelected = collect(['period', 'date', 'week', 'month', 'start_date', 'end_date'])->contains(fn (string $key) => array_key_exists($key, $filters));
        $period = ReportingPeriod::fromInput($filters);
        $periodBills = fn (Builder $bills): Builder => $bills
            ->where('status', 'completed')
            ->where('billed_at', '>=', $period->start->format('Y-m-d H:i:s'))
            ->where('billed_at', '<', $period->endExclusive->format('Y-m-d H:i:s'));
        $customers = Customer::query()
            ->when($periodSelected, fn (Builder $query) => $query
                ->withCount(['bills as report_visit_count' => $periodBills])
                ->withSum(['bills as report_total_spent' => $periodBills], 'grand_total')
                ->withMax(['bills as report_last_visit' => $periodBills], 'billed_at'))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%")
                        ->orWhere('customer_code', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('filter'), function (Builder $query) use ($request): void {
                match ($request->string('filter')->toString()) {
                    'regular' => $query->where('total_visits', '>=', 2),
                    'vip' => $query->where('total_spent', '>=', 5000),
                    'new' => $query->where('total_visits', '<=', 1),
                    'birthdays' => $query->whereMonth('date_of_birth', now('Asia/Kolkata')->month),
                    'not_visited_30' => $query->where(fn ($query) => $query->whereNull('last_visit_at')->orWhere('last_visit_at', '<', now('Asia/Kolkata')->subDays(30))),
                    'not_visited_60' => $query->where(fn ($query) => $query->whereNull('last_visit_at')->orWhere('last_visit_at', '<', now('Asia/Kolkata')->subDays(60))),
                    'not_visited_90' => $query->where(fn ($query) => $query->whereNull('last_visit_at')->orWhere('last_visit_at', '<', now('Asia/Kolkata')->subDays(90))),
                    'top_spending' => $query->orderByDesc($periodSelected ? 'report_total_spent' : 'total_spent'),
                    'inactive' => $query->where('status', 'inactive'),
                    default => $query->where('status', 'active'),
                };
            })
            ->when($periodSelected, fn (Builder $query) => $query->whereHas('bills', $periodBills))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.customers.index', [
            'period' => $period,
            'periodSelected' => $periodSelected,
            'customers' => $customers,
        ]);
    }

    public function create(): View
    {
        return view('admin.customers.create');
    }

    public function show(Customer $customer): View
    {
        $bills = Bill::query()
            ->with(['items', 'payments', 'billedBy'])
            ->where('customer_id', $customer->id)
            ->latest('billed_at')
            ->paginate(10);

        return view('admin.customers.show', [
            'customer' => $customer,
            'bills' => $bills,
            'favouriteService' => $bills->getCollection()
                ->flatMap->items
                ->groupBy('service_name_snapshot')
                ->sortByDesc(fn ($items) => $items->sum('quantity'))
                ->keys()
                ->first(),
            'lastStaff' => $bills->getCollection()->first()?->billedBy?->name,
        ]);
    }

    public function edit(Customer $customer): View
    {
        return view('admin.customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $request->validate(['mobile' => ['required', 'string', 'max:30']]);
        $mobile = Customer::normalizeMobile((string) $request->input('mobile'));
        if (! preg_match('/^[6-9]\d{9}$/', $mobile)) {
            throw ValidationException::withMessages(['mobile' => 'Enter a valid Indian mobile number.']);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z]+(?: [A-Za-z]+)*$/'],
            'mobile' => [Rule::unique('customers', 'mobile')->ignore($customer->id)],
        ]);

        $customer->update([
            'name' => Str::title(Str::lower(preg_replace('/\s+/', ' ', trim($validated['name'])))),
            'mobile' => $mobile,
        ]);

        return redirect()->route('admin.customers.show', $customer)->with('status', 'Customer details updated.');
    }

    public function whatsapp(Customer $customer): RedirectResponse
    {
        if ($customer->whatsapp_status === 'not_contacted') {
            $customer->forceFill(['whatsapp_status' => 'whatsapp_sent'])->save();
        }

        return redirect()->away('https://wa.me/91'.$customer->mobile);
    }

    public function store(Request $request): RedirectResponse
    {
        $mobile = Customer::normalizeMobile((string) $request->input('mobile'));
        if (! preg_match('/^[6-9]\d{9}$/', $mobile)) {
            throw ValidationException::withMessages(['mobile' => 'Enter a valid Indian mobile number.']);
        }

        if (Customer::query()->where('mobile', $mobile)->exists()) {
            throw ValidationException::withMessages(['mobile' => 'A customer with this mobile number already exists.']);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z]+(?: [A-Za-z]+)*$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'gender' => ['nullable', Rule::in(['female', 'male', 'other'])],
            'area' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        Customer::query()->create([
            ...$validated,
            'name' => Str::title(Str::lower(preg_replace('/\s+/', ' ', trim($validated['name'])))),
            'mobile' => $mobile,
            'customer_code' => $this->nextCustomerCode(),
            'status' => 'active',
            'whatsapp_status' => 'not_contacted',
        ]);

        return redirect()->route('admin.customers.index')->with('status', 'Customer added.');
    }

    private function nextCustomerCode(): string
    {
        return (new \App\Services\CustomerCodeGenerator)->generate();
    }
}
