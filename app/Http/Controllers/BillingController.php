<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Bill;
use App\Models\Customer;
use App\Models\SalonSetting;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function create(Request $request): View
    {
        $services = Service::query()
            ->with('category')
            ->where('status', 'active')
            ->get()
            ->sortBy(fn (Service $service) => [
                $service->category?->name ?? '',
                $service->name,
            ])
            ->values();

        $categories = ServiceCategory::query()
            ->whereIn('id', $services->pluck('category_id')->filter()->unique())
            ->orderBy('name')
            ->get();

        $appointment = null;
        if ($request->filled('appointment_id')) {
            $appointment = Appointment::query()
                ->with(['customer', 'appointmentServices.service.category'])
                ->find($request->integer('appointment_id'));
        }

        $todayBillsQuery = Bill::query()
            ->with(['customer', 'payments'])
            ->whereDate('billed_at', now('Asia/Kolkata')->toDateString())
            ->latest('billed_at');

        if ($request->user()->isStaff()) {
            $todayBillsQuery->where('billed_by', $request->user()->id);
        }

        return view('billing.create', [
            'services' => $services,
            'categories' => $categories,
            'appointment' => $appointment,
            'todayBills' => $todayBillsQuery->limit(10)->get(),
            'staff' => User::query()->where('role', 'staff')->where('status', 'active')->orderBy('name')->get(),
            'biller' => $request->user(),
            'idempotencyKey' => old('idempotency_key', (string) Str::uuid()),
        ]);
    }

    public function lookupCustomer(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', $request->query('mobile', '')));
        $mobile = Customer::normalizeMobile($query);

        abort_if(strlen($query) < 2 && strlen($mobile) < 2, 422, 'Enter at least 2 characters to search customers.');

        $customers = Customer::query()
            ->where('status', 'active')
            ->where(function ($builder) use ($query, $mobile): void {
                if ($mobile !== '') {
                    $builder->where('mobile', 'like', '%'.$mobile.'%')
                        ->orWhere('alternate_mobile', 'like', '%'.$mobile.'%');
                }

                if ($query !== '' && ! ctype_digit($query)) {
                    $builder->orWhere('name', 'like', '%'.$query.'%');

                    if (strlen($query) >= 3) {
                        $builder->orWhere('name', 'like', substr($query, 0, 3).'%');
                    }
                }
            })
            ->orderByRaw('CASE WHEN mobile = ? THEN 0 WHEN mobile LIKE ? THEN 1 ELSE 2 END', [$mobile, $mobile.'%'])
            ->orderByDesc('last_visit_at')
            ->orderBy('name')
            ->limit(8)
            ->get();

        $customer = strlen($mobile) === 10
            ? $customers->firstWhere('mobile', $mobile)
            : $customers->first();

        return response()->json([
            'found' => (bool) $customer,
            'customers' => $customers->map(fn (Customer $customer) => [
                'id' => $customer->id,
                'name' => $customer->name,
                'mobile' => $customer->mobile,
                'last_visit_at' => $customer->last_visit_at?->format('d M Y'),
            ])->values(),
            'customer' => $customer ? [
                'id' => $customer->id,
                'name' => $customer->name,
                'mobile' => $customer->mobile,
                'last_visit_at' => $customer->last_visit_at?->format('d M Y'),
            ] : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'appointment_id' => ['nullable', 'exists:appointments,id'],
            'customer_mobile' => ['required', 'string', 'max:30'],
            'customer_name' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z]+(?: [A-Za-z]+)*$/'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.service_id' => ['required', 'exists:services,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'items.*.confirmed_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.service_performed_by' => ['nullable', 'exists:users,id'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'home_visit_charge' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:cash,upi,card,split,other'],
            'payment_note' => ['nullable', 'string', 'max:255'],
            'split_payments' => ['exclude_unless:payment_method,split', 'required_if:payment_method,split', 'array', 'min:1'],
            'split_payments.*.method' => ['exclude_unless:payment_method,split', 'required', 'in:cash,upi,card,other'],
            'split_payments.*.amount' => ['exclude_unless:payment_method,split', 'required', 'numeric', 'min:0.01'],
            'idempotency_key' => ['required', 'string', 'max:100'],
        ]);

        if ($existing = Bill::query()->where('idempotency_key', $validated['idempotency_key'])->first()) {
            return $this->redirectToSuccess($request, $existing);
        }

        $mobile = Customer::normalizeMobile($validated['customer_mobile']);
        if (! preg_match('/^[6-9]\d{9}$/', $mobile)) {
            throw ValidationException::withMessages(['customer_mobile' => 'Enter a valid Indian mobile number.']);
        }
        $customerName = $this->normalizeCustomerName($validated['customer_name']);

        try {
            $bill = DB::transaction(function () use ($request, $validated, $mobile, $customerName): Bill {
                $appointment = null;
                if (filled($validated['appointment_id'] ?? null)) {
                    $appointment = Appointment::query()
                        ->with('customer')
                        ->lockForUpdate()
                        ->findOrFail($validated['appointment_id']);

                    if ($appointment->customer?->mobile !== $mobile) {
                        throw ValidationException::withMessages([
                            'customer_mobile' => 'The customer mobile must match the selected appointment.',
                        ]);
                    }
                }

                $customer = Customer::query()->firstOrCreate(
                    ['mobile' => $mobile],
                    [
                        'customer_code' => $this->nextCustomerCode(),
                        'name' => $customerName,
                        'status' => 'active',
                    ],
                );
                $customer->update(['name' => $customerName]);

                $subtotalCents = 0;
                $items = [];
                $serviceIds = collect($validated['items'])->pluck('service_id')->unique()->values();
                if ($serviceIds->count() !== count($validated['items'])) {
                    throw ValidationException::withMessages(['items' => 'A service can only be added once. Use quantity buttons for repeats.']);
                }

                $services = Service::query()
                    ->with('category')
                    ->whereIn('id', $serviceIds)
                    ->get()
                    ->keyBy('id');

                foreach ($validated['items'] as $index => $item) {
                    $service = $services->get((int) $item['service_id']);
                    if (! $service || $service->status !== 'active') {
                        throw ValidationException::withMessages(["items.$index.service_id" => 'Selected service is not billable.']);
                    }

                    $unitCents = $this->billableUnitCents($service, $item['confirmed_price'] ?? null, $index);
                    $quantity = (int) $item['quantity'];
                    $lineCents = $unitCents * $quantity;
                    $subtotalCents += $lineCents;
                    $performerId = $request->user()->isAdmin()
                        ? ($item['service_performed_by'] ?? $request->user()->id)
                        : $request->user()->id;

                    $items[] = compact('service', 'quantity', 'unitCents', 'lineCents', 'performerId');
                }

                $discountCents = $this->toCents($validated['discount_amount'] ?? 0);
                $homeVisitCents = $this->toCents($validated['home_visit_charge'] ?? 0);

                if ($discountCents > $subtotalCents) {
                    throw ValidationException::withMessages(['discount_amount' => 'Discount cannot exceed subtotal.']);
                }

                $grandCents = max(0, $subtotalCents - $discountCents + $homeVisitCents);
                $payments = $this->validatedPayments($validated, $grandCents);

                $bill = Bill::query()->create([
                    'invoice_number' => $this->nextInvoiceNumber(),
                    'customer_id' => $customer->id,
                    'appointment_id' => $appointment?->id,
                    'appointment_booking_number' => $appointment?->booking_number,
                    'billed_by' => $request->user()->id,
                    'created_by' => $request->user()->id,
                    'subtotal' => $this->fromCents($subtotalCents),
                    'discount_amount' => $this->fromCents($discountCents),
                    'home_visit_charge' => $this->fromCents($homeVisitCents),
                    'grand_total' => $this->fromCents($grandCents),
                    'payment_status' => 'paid',
                    'status' => 'completed',
                    'idempotency_key' => $validated['idempotency_key'],
                    'billed_at' => now('Asia/Kolkata'),
                ]);

                foreach ($items as $item) {
                    $bill->items()->create([
                        'service_id' => $item['service']->id,
                        'service_performed_by' => $item['performerId'],
                        'service_name_snapshot' => $item['service']->name,
                        'service_code_snapshot' => $item['service']->service_code,
                        'category_name_snapshot' => $item['service']->publicCategoryName(),
                        'is_package_snapshot' => $item['service']->is_package,
                        'quantity' => $item['quantity'],
                        'unit_price' => $this->fromCents($item['unitCents']),
                        'line_total' => $this->fromCents($item['lineCents']),
                        'price_was_confirmed' => $item['service']->hasEstimatedPrice(),
                    ]);
                }

                foreach ($payments as $payment) {
                    $bill->payments()->create([
                        ...$payment,
                        'received_by' => $request->user()->id,
                        'paid_at' => now('Asia/Kolkata'),
                    ]);
                }

                if ($appointment && in_array($appointment->status, ['pending', 'confirmed', 'in_progress'], true)) {
                    $appointment->update(['status' => 'completed']);
                }

                Customer::query()->whereKey($customer->id)->update([
                    'total_visits' => DB::raw('total_visits + 1'),
                    'total_spent' => DB::raw('total_spent + '.$this->fromCents($grandCents)),
                    'last_visit_at' => now('Asia/Kolkata'),
                ]);

                return $bill;
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (QueryException $exception) {
            if ($existing = Bill::query()->where('idempotency_key', $validated['idempotency_key'])->first()) {
                return $this->redirectToSuccess($request, $existing);
            }

            Log::error('Billing could not be completed.', [
                'user_id' => $request->user()->id,
                'idempotency_key' => $validated['idempotency_key'],
                'exception' => $exception,
            ]);

            return back()
                ->withInput()
                ->withErrors(['billing' => 'Billing could not be completed. No invoice was created. Please try again.']);
        } catch (\Throwable $exception) {
            Log::error('Billing could not be completed.', [
                'user_id' => $request->user()->id,
                'idempotency_key' => $validated['idempotency_key'],
                'exception' => $exception,
            ]);

            return back()
                ->withInput()
                ->withErrors(['billing' => 'Billing could not be completed. No invoice was created. Please try again.']);
        }

        return $this->redirectToSuccess($request, $bill);
    }

    public function success(Request $request, Bill $bill): View
    {
        $this->authorizeBill($request, $bill);

        return view('billing.complete', $this->invoiceData($bill));
    }

    public function show(Request $request, Bill $bill): View
    {
        $this->authorizeBill($request, $bill);

        return view('billing.show', $this->invoiceData($bill));
    }

    public function print(Request $request, Bill $bill): View
    {
        $this->authorizeBill($request, $bill);

        return view('billing.invoice-print', $this->invoiceData($bill));
    }

    public function pdf(Request $request, Bill $bill)
    {
        $this->authorizeBill($request, $bill);

        try {
            $pdf = Pdf::loadView('billing.invoice-pdf', $this->invoiceData($bill))->setPaper('a4');

            return $pdf->download(str_replace(['/', '\\'], '-', $bill->invoice_number).'.pdf');
        } catch (\Throwable $exception) {
            Log::error('Invoice PDF could not be generated.', [
                'bill_id' => $bill->id,
                'invoice_number' => $bill->invoice_number,
                'exception' => $exception,
            ]);

            return back()->withErrors(['pdf' => 'PDF could not be generated. The invoice is saved. Please try downloading it again.']);
        }
    }

    public function whatsapp(Request $request, Bill $bill): RedirectResponse
    {
        $this->authorizeBill($request, $bill);
        $bill->load('customer', 'payments');
        $message = "Hello {$bill->customer->name},\n\nThank you for visiting 5 Star New Look Salon.\n\nInvoice:\n{$bill->invoice_number}\n\nAmount Paid:\n".Money::inr($bill->grand_total)."\n\nPlease find your invoice attached.\n\nThank you.\n\nStaff will manually attach the PDF.";

        return redirect()->away('https://wa.me/91'.$bill->customer->mobile.'?text='.rawurlencode($message));
    }

    private function invoiceData(Bill $bill): array
    {
        return [
            'bill' => $bill->load(['customer', 'billedBy', 'items.performer', 'payments']),
            'settings' => SalonSetting::cached(),
            'logoDataUri' => $this->logoDataUri(),
        ];
    }

    private function authorizeBill(Request $request, Bill $bill): void
    {
        abort_if($request->user()->isStaff() && $bill->billed_by !== $request->user()->id, 403);
    }

    private function routePrefix(Request $request): string
    {
        return $request->user()->isAdmin() ? 'admin' : 'staff';
    }

    private function redirectToSuccess(Request $request, Bill $bill): RedirectResponse
    {
        return new RedirectResponse(route($this->routePrefix($request).'.billing.success', $bill, false));
    }

    private function normalizeCustomerName(string $name): string
    {
        $name = preg_replace('/\s+/', ' ', trim($name)) ?: '';

        return Str::title(Str::lower($name));
    }

    private function billableUnitCents(Service $service, mixed $confirmedPrice, int $index): int
    {
        if ($service->hasEstimatedPrice()) {
            if (! filled($confirmedPrice)) {
                throw ValidationException::withMessages(["items.$index.confirmed_price" => 'Confirmed Price is required for this service.']);
            }

            return $this->toCents($confirmedPrice);
        }

        return $this->toCents($service->discounted_price ?: $service->price);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function validatedPayments(array $validated, int $grandCents): array
    {
        if ($validated['payment_method'] === 'other' && blank($validated['payment_note'] ?? null)) {
            throw ValidationException::withMessages(['payment_note' => 'A short note is required for Other payment.']);
        }

        if ($validated['payment_method'] !== 'split') {
            return [[
                'payment_method' => $validated['payment_method'],
                'amount' => $this->fromCents($grandCents),
                'method_note' => $validated['payment_note'] ?? null,
            ]];
        }

        $payments = [];
        $total = 0;
        foreach ($validated['split_payments'] ?? [] as $index => $payment) {
            if (blank($payment['method'] ?? null) || blank($payment['amount'] ?? null)) {
                continue;
            }

            $amount = $this->toCents($payment['amount']);
            $total += $amount;
            $payments[] = [
                'payment_method' => $payment['method'],
                'amount' => $this->fromCents($amount),
                'method_note' => $validated['payment_note'] ?? null,
            ];
        }

        if ($payments === [] || $total !== $grandCents) {
            throw ValidationException::withMessages(['split_payments' => 'Split payments must exactly match the grand total.']);
        }

        return $payments;
    }

    private function nextInvoiceNumber(): string
    {
        $prefix = SalonSetting::getValue('invoice_prefix', '5STAR') ?: '5STAR';
        $period = now('Asia/Kolkata')->format('Y/m');
        $stem = $prefix.'/'.$period.'/';
        $latest = Bill::query()
            ->where('invoice_number', 'like', $stem.'%')
            ->lockForUpdate()
            ->orderByDesc('invoice_number')
            ->value('invoice_number');
        $next = $latest ? ((int) Str::afterLast($latest, '/') + 1) : 1;

        return $stem.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    private function nextCustomerCode(): string
    {
        return (new \App\Services\CustomerCodeGenerator)->generate();
    }

    private function toCents(mixed $value): int
    {
        $normalized = preg_replace('/[^\d.]/', '', (string) $value) ?: '0';
        [$whole, $decimal] = array_pad(explode('.', $normalized, 2), 2, '0');

        return ((int) $whole * 100) + (int) str_pad(substr($decimal, 0, 2), 2, '0');
    }

    private function fromCents(int $cents): string
    {
        $whole = intdiv($cents, 100);
        $decimal = abs($cents % 100);

        return $whole.'.'.str_pad((string) $decimal, 2, '0', STR_PAD_LEFT);
    }

    private function logoDataUri(): ?string
    {
        $path = public_path('images/brand/5-star-new-look-salon-logo.png');
        if (! File::exists($path)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode(File::get($path));
    }
}
