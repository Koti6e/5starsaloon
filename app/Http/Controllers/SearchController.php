<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Customer;
use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));
        $user = $request->user();
        $billQuery = Bill::query()
            ->with(['customer', 'payments'])
            ->when($user->isStaff(), fn (Builder $builder) => $builder->where('billed_by', $user->id));

        return view('search.index', [
            'query' => $query,
            'routeRoot' => $user->isAdmin() ? 'admin' : 'staff',
            'customers' => $user->isAdmin() && $query !== ''
                ? Customer::query()
                    ->where(fn (Builder $builder) => $builder
                        ->where('name', 'like', "%{$query}%")
                        ->orWhere('mobile', 'like', "%{$query}%")
                        ->orWhere('customer_code', 'like', "%{$query}%"))
                    ->latest()
                    ->limit(8)
                    ->get()
                : collect(),
            'bills' => $query !== ''
                ? $billQuery
                    ->where(fn (Builder $builder) => $builder
                        ->where('invoice_number', 'like', "%{$query}%")
                        ->orWhereHas('customer', fn (Builder $customer) => $customer
                            ->where('name', 'like', "%{$query}%")
                            ->orWhere('mobile', 'like', "%{$query}%")))
                    ->latest('billed_at')
                    ->limit(8)
                    ->get()
                : collect(),
            'services' => $query !== ''
                ? Service::query()
                    ->where(fn (Builder $builder) => $builder
                        ->where('name', 'like', "%{$query}%")
                        ->orWhere('service_code', 'like', "%{$query}%"))
                    ->latest()
                    ->limit(8)
                    ->get()
                : collect(),
        ]);
    }
}
