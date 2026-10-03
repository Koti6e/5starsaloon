<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalonClosedDate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalonClosedDateController extends Controller
{
    public function index(): View
    {
        return view('admin.closed-dates.index', [
            'closedDates' => SalonClosedDate::query()->orderBy('date')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d', 'unique:salon_closed_dates,date'],
            'name' => ['nullable', 'string', 'max:120'],
        ]);
        SalonClosedDate::query()->create([...$data, 'is_active' => true]);
        return back()->with('status', 'Salon closure date added.');
    }

    public function update(Request $request, SalonClosedDate $closedDate): RedirectResponse
    {
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $closedDate->update($data);
        return back()->with('status', 'Salon closure date updated.');
    }

    public function destroy(SalonClosedDate $closedDate): RedirectResponse
    {
        $closedDate->delete();
        return back()->with('status', 'Salon closure date removed.');
    }
}
