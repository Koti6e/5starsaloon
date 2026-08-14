<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\ContactEnquiry;
use App\Models\Customer;
use App\Models\Gallery;
use App\Models\SalonSetting;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\AppointmentNumberGenerator;
use App\Services\CustomerCodeGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PublicPageController extends Controller
{
    public function home(): View
    {
        return view('public.home', [
            'settings' => SalonSetting::cached(),
            'featuredServices' => Service::query()
                ->with(['category', 'images'])
                ->publiclyVisible()
                ->where('is_featured', true)
                ->where('is_package', false)
                ->orderBy('display_order')
                ->limit(6)
                ->get(),
            'categories' => ServiceCategory::query()
                ->where('is_active', true)
                ->orderBy('display_order')
                ->get(),
            'galleryImages' => Gallery::query()
                ->where('status', 'active')
                ->where('is_featured', true)
                ->orderBy('display_order')
                ->limit(5)
                ->get(),
            'packageServices' => Service::query()
                ->with(['category', 'images'])
                ->publiclyVisible()
                ->where('is_package', true)
                ->orderBy('display_order')
                ->limit(6)
                ->get(),
            'hairServices' => $this->servicesForCategory('haircuts-grooming'),
            'facialServices' => $this->servicesForCategory('facial-cleanup'),
            'colourServices' => $this->servicesForCategory('hair-colouring'),
            'oilServices' => $this->servicesForCategory('oil-massage'),
        ]);
    }

    public function services(Request $request): View
    {
        $query = Service::query()->with(['category', 'images'])->publiclyVisible();

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($category) => $category->where('slug', $request->string('category')));
        }

        if ($request->filled('search')) {
            $search = '%'.$request->string('search')->trim().'%';
            $query->where(fn ($service) => $service
                ->where('name', 'like', $search)
                ->orWhere('short_description', 'like', $search)
                ->orWhereHas('category', fn ($category) => $category
                    ->where('name', 'like', $search)
                    ->orWhere('description', 'like', $search))
            );
        }

        if ($request->boolean('home_service')) {
            $query->where('is_home_service_available', true);
        }

        if ($request->filled('price_type')) {
            $query->where('price_type', $request->string('price_type'));
        }

        if ($request->boolean('packages')) {
            $query->where('is_package', true);
        }

        match ($request->string('sort')->toString()) {
            'price_desc' => $query->orderByRaw('COALESCE(discounted_price, price, minimum_price, maximum_price) desc'),
            'price_asc' => $query->orderByRaw('COALESCE(discounted_price, price, minimum_price, maximum_price) asc'),
            default => $query->orderBy('display_order')->orderBy('name'),
        };

        return view('public.services.index', [
            'services' => $query->paginate(12)->withQueryString(),
            'categories' => ServiceCategory::query()->where('is_active', true)->orderBy('display_order')->get(),
            'settings' => SalonSetting::cached(),
        ]);
    }

    public function service(Service $service): View
    {
        abort_unless($service->status === 'active' && $service->category?->is_active, 404);

        return view('public.services.show', [
            'service' => $service->load(['category', 'images']),
            'relatedServices' => Service::query()
                ->with(['category', 'images'])
                ->publiclyVisible()
                ->where('category_id', $service->category_id)
                ->whereKeyNot($service->getKey())
                ->limit(3)
                ->get(),
            'settings' => SalonSetting::cached(),
        ]);
    }

    public function about(): View
    {
        return view('public.about', ['settings' => SalonSetting::cached()]);
    }

    public function gallery(): View
    {
        return view('public.gallery', [
            'settings' => SalonSetting::cached(),
            'images' => Gallery::query()->where('status', 'active')->orderBy('display_order')->paginate(18),
        ]);
    }

    public function contact(): View
    {
        return view('public.contact', ['settings' => SalonSetting::cached()]);
    }

    public function storeContact(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'mobile' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:160'],
            'subject' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:2000'],
            'consent' => ['accepted'],
            'website' => ['nullable', 'size:0'],
        ]);

        ContactEnquiry::query()->create([
            ...collect($validated)->except(['consent', 'website'])->all(),
            'consented_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return back()->with('status', 'Thank you. Your enquiry has been saved and our team can follow up.');
    }

    public function bookAppointment(): View
    {
        return view('public.book-appointment', [
            'settings' => SalonSetting::cached(),
            'services' => Service::query()->with(['category', 'images'])->publiclyVisible()->orderBy('name')->get(),
        ]);
    }

    public function storeAppointment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'appointment_type' => ['required', 'string', 'in:salon_visit,home_service'],
            'service_slug' => ['required', 'string', 'exists:services,slug'],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_time' => ['required', 'date_format:H:i'],
            'customer_name' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z]+(?: [A-Za-z]+)*$/'],
            'mobile' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:160'],
            'address' => ['required_if:appointment_type,home_service', 'nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'consent' => ['accepted'],
        ], [
            'appointment_type.required' => 'Please select an appointment type.',
            'service_slug.required' => 'Please select a valid service.',
            'appointment_date.required' => 'Please choose a valid appointment date.',
            'appointment_date.after_or_equal' => 'Appointment date cannot be in the past.',
            'appointment_time.required' => 'Please select a preferred time.',
            'customer_name.required' => 'Your full name is required.',
            'customer_name.regex' => 'Name should contain alphabetic characters only.',
            'mobile.required' => 'Mobile number is required.',
            'address.required_if' => 'Complete address is required for Elite Home Service appointments.',
            'consent.accepted' => 'You must agree to be contacted about your booking.',
        ]);

        $mobile = Customer::normalizeMobile($validated['mobile']);
        if (! preg_match('/^[6-9]\d{9}$/', $mobile)) {
            throw ValidationException::withMessages(['mobile' => 'Please enter a valid 10-digit Indian mobile number.']);
        }

        $service = Service::query()
            ->publiclyVisible()
            ->where('slug', $validated['service_slug'])
            ->firstOrFail();

        if ($validated['appointment_type'] === 'home_service' && ! $service->is_home_service_available) {
            throw ValidationException::withMessages(['appointment_type' => 'Selected service is not available for home visits.']);
        }

        $customerName = Str::title(Str::lower(preg_replace('/\s+/', ' ', trim($validated['customer_name']))));
        $appointmentStart = Carbon::createFromFormat(
            'Y-m-d H:i',
            $validated['appointment_date'].' '.$validated['appointment_time'],
            'Asia/Kolkata',
        );
        $durationMinutes = $service->duration_minutes ?: 30;
        $appointmentEnd = $appointmentStart->copy()->addMinutes($durationMinutes);

        $this->validateAppointmentSlot($appointmentStart, $appointmentEnd);

        $duplicate = Appointment::query()
            ->where('appointment_type', $validated['appointment_type'])
            ->whereDate('date', $validated['appointment_date'])
            ->where('start_time', $appointmentStart->format('H:i:s'))
            ->where('status', '!=', 'cancelled')
            ->whereHas('customer', fn ($customer) => $customer->where('mobile', $mobile))
            ->whereHas('appointmentServices', fn ($appointmentService) => $appointmentService->where('service_id', $service->id))
            ->latest('id')
            ->first();

        if ($duplicate?->confirmation_token) {
            return redirect()->route('appointments.confirmed', ['token' => $duplicate->confirmation_token]);
        }

        $appointment = DB::transaction(function () use ($validated, $mobile, $customerName, $service, $durationMinutes): Appointment {
            $customer = Customer::query()->firstOrCreate(
                ['mobile' => $mobile],
                [
                    'customer_code' => (new CustomerCodeGenerator)->generate(),
                    'name' => $customerName,
                    'email' => $validated['email'] ?? null,
                    'status' => 'active',
                ],
            );
            $customer->update(['name' => $customerName]);
            if (filled($validated['email'] ?? null)) {
                $customer->update(['email' => $validated['email']]);
            }

            $unitPrice = $service->discounted_price ?: $service->price ?: $service->minimum_price ?: 0;
            $visitCharge = $validated['appointment_type'] === 'home_service' ? ($service->home_service_visit_charge ?? 0) : 0;
            $total = $unitPrice + $visitCharge;

            $startTime = $validated['appointment_time'];
            $estimatedEndTime = Carbon::parse($startTime)->addMinutes($durationMinutes)->format('H:i:s');

            $appointment = Appointment::query()->create([
                'booking_number' => (new AppointmentNumberGenerator)->generate(),
                'confirmation_token' => Str::random(40),
                'customer_id' => $customer->id,
                'appointment_type' => $validated['appointment_type'],
                'date' => $validated['appointment_date'],
                'start_time' => $startTime,
                'estimated_end_time' => $estimatedEndTime,
                'subtotal' => $unitPrice,
                'visit_charge' => $visitCharge,
                'discount' => 0,
                'total' => $total,
                'status' => 'pending',
                'customer_notes' => $validated['notes'] ?? null,
                'address_line_1' => $validated['appointment_type'] === 'home_service' ? ($validated['address'] ?? null) : null,
            ]);

            AppointmentService::query()->create([
                'appointment_id' => $appointment->id,
                'service_id' => $service->id,
                'service_name_snapshot' => $service->name,
                'unit_price' => $unitPrice,
                'duration_minutes' => $durationMinutes,
            ]);

            return $appointment;
        });

        return redirect()->route('appointments.confirmed', ['token' => $appointment->confirmation_token]);
    }

    public function appointmentConfirmed(string $token): View
    {
        $appointment = Appointment::query()
            ->with(['customer', 'appointmentServices.service'])
            ->where('confirmation_token', $token)
            ->firstOrFail();

        $settings = SalonSetting::cached();
        $appointmentService = $appointment->appointmentServices->first();
        $serviceName = $appointmentService?->service_name_snapshot ?? 'Salon Service';
        $customerName = $appointment->customer?->name ?? 'Valued Customer';
        $mobile = $appointment->customer?->mobile ?? '';
        $typeLabel = $appointment->appointment_type === 'home_service' ? 'Elite Home Service' : 'Salon Visit';
        $dateStr = $appointment->date?->format('d M Y') ?? (string) $appointment->date;
        $timeStr = Carbon::parse($appointment->start_time)->format('h:i A');
        $addressStr = $appointment->appointment_type === 'home_service' ? ($appointment->address_line_1 ?: 'Not provided') : 'N/A';
        $notesStr = $appointment->customer_notes ?: 'None';

        $rawMessage = "New Appointment Booking\n\n".
            "Appointment No: {$appointment->booking_number}\n".
            "Customer: {$customerName}\n".
            "Mobile: {$mobile}\n".
            "Service: {$serviceName}\n".
            "Type: {$typeLabel}\n".
            "Date: {$dateStr}\n".
            "Time: {$timeStr}\n".
            "Address: {$addressStr}\n".
            "Notes: {$notesStr}\n\n".
            "Please confirm this booking.";

        $whatsappNum = preg_replace('/\D+/', '', $settings['whatsapp_number'] ?? $settings['primary_phone'] ?? '919876543210');
        if (strlen($whatsappNum) === 10) {
            $whatsappNum = '91'.$whatsappNum;
        }

        $whatsappUrl = 'https://wa.me/'.$whatsappNum.'?text='.rawurlencode($rawMessage);

        return view('public.appointment-confirmed', [
            'appointment' => $appointment,
            'appointmentService' => $appointmentService,
            'settings' => $settings,
            'whatsappUrl' => $whatsappUrl,
        ]);
    }

    private function servicesForCategory(string $slug)
    {
        return Service::query()
            ->with(['category', 'images'])
            ->publiclyVisible()
            ->where('is_package', false)
            ->whereHas('category', fn ($category) => $category->where('slug', $slug))
            ->orderBy('display_order')
            ->limit(4)
            ->get();
    }

    private function validateAppointmentSlot(Carbon $start, Carbon $end): void
    {
        if ($start->lte(now('Asia/Kolkata'))) {
            throw ValidationException::withMessages([
                'appointment_time' => 'Please choose a future appointment time.',
            ]);
        }

        $slotDuration = max(15, (int) (SalonSetting::getValue('appointment_slot_duration', '30') ?: 30));
        if (($start->hour * 60 + $start->minute) % $slotDuration !== 0) {
            throw ValidationException::withMessages([
                'appointment_time' => 'Please choose a valid appointment slot.',
            ]);
        }

        [$opensAt, $closesAt] = $this->workingHoursFor($start);

        if ($start->lt($opensAt) || $end->gt($closesAt)) {
            throw ValidationException::withMessages([
                'appointment_time' => 'Please choose a time within salon working hours.',
            ]);
        }
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function workingHoursFor(Carbon $date): array
    {
        $workingHours = SalonSetting::getValue('working_hours', '') ?: '';
        $open = '09:00';
        $close = '20:00';

        if (preg_match('/(\d{1,2})(?::(\d{2}))?\s*(am|pm)?\s*(?:-|to|–|—)\s*(\d{1,2})(?::(\d{2}))?\s*(am|pm)?/i', $workingHours, $matches)) {
            $open = $this->normalizeWorkingHour($matches[1], $matches[2] ?? '00', $matches[3] ?? null);
            $close = $this->normalizeWorkingHour($matches[4], $matches[5] ?? '00', $matches[6] ?? null);
        }

        return [
            $date->copy()->setTimeFromTimeString($open),
            $date->copy()->setTimeFromTimeString($close),
        ];
    }

    private function normalizeWorkingHour(string $hour, string $minute, ?string $meridian): string
    {
        $hourInt = (int) $hour;
        $minuteInt = (int) $minute;

        if ($meridian) {
            $meridian = strtolower($meridian);
            if ($meridian === 'pm' && $hourInt < 12) {
                $hourInt += 12;
            }
            if ($meridian === 'am' && $hourInt === 12) {
                $hourInt = 0;
            }
        }

        return str_pad((string) $hourInt, 2, '0', STR_PAD_LEFT).':'.str_pad((string) $minuteInt, 2, '0', STR_PAD_LEFT);
    }
}
