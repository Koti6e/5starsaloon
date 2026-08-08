<?php

use App\Http\Controllers\Admin\AboutSalonOsController;
use App\Http\Controllers\Admin\AppointmentController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\ServiceController as AdminServiceController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\StaffController as AdminStaffController;
use App\Http\Controllers\Admin\StaffPasswordController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Staff\DashboardController as StaffDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicPageController::class, 'home'])->name('home');
Route::get('/services', [PublicPageController::class, 'services'])->name('services.index');
Route::get('/services/{service}', [PublicPageController::class, 'service'])->name('services.show');
Route::get('/gallery', [PublicPageController::class, 'gallery'])->name('gallery');
Route::get('/about', [PublicPageController::class, 'about'])->name('about');
Route::get('/contact', [PublicPageController::class, 'contact'])->name('contact');
Route::get('/sitemap.xml', function () {
    $urls = collect([
        route('home'),
        route('services.index'),
        route('gallery'),
        route('about'),
        route('contact'),
        route('appointments.book'),
    ]);

    $xml = view('public.sitemap', ['urls' => $urls])->render();

    return response($xml, 200)->header('Content-Type', 'application/xml');
})->name('sitemap');
Route::post('/contact', [PublicPageController::class, 'storeContact'])
    ->middleware('throttle:5,1')
    ->name('contact.store');
Route::get('/book-appointment', [PublicPageController::class, 'bookAppointment'])->name('appointments.book');
Route::post('/book-appointment', [PublicPageController::class, 'storeAppointment'])
    ->middleware('throttle:10,1')
    ->name('appointments.store');
Route::get('/appointment-confirmed/{token}', [PublicPageController::class, 'appointmentConfirmed'])->name('appointments.confirmed');

Route::get('/dashboard', function () {
    $user = request()->user();

    return redirect()->route($user->isAdmin() ? 'admin.dashboard' : 'staff.dashboard');
})->middleware(['auth', 'active', 'password.changed'])->name('dashboard');

Route::middleware(['auth', 'active', 'password.changed'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'active', 'password.changed', 'role:admin'])
    ->group(function (): void {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
        Route::get('/search', SearchController::class)->name('search');
        Route::get('/reports', AdminReportController::class)->name('reports.index');
        Route::get('/about-salonos', AboutSalonOsController::class)->name('about-salonos');
        Route::get('/billing/create', [BillingController::class, 'create'])->name('billing.create');
        Route::post('/billing', [BillingController::class, 'store'])->name('billing.store');
        Route::get('/billing/customer-lookup', [BillingController::class, 'lookupCustomer'])->name('billing.customer-lookup');
        Route::get('/billing/{bill}/success', [BillingController::class, 'success'])->name('billing.success');
        Route::get('/billing/{bill}/complete', [BillingController::class, 'success'])->name('billing.complete');
        Route::get('/billing/{bill}', [BillingController::class, 'show'])->name('billing.show');
        Route::get('/billing/{bill}/print', [BillingController::class, 'print'])->name('billing.print');
        Route::get('/billing/{bill}/pdf', [BillingController::class, 'pdf'])->name('billing.pdf');
        Route::get('/billing/{bill}/whatsapp', [BillingController::class, 'whatsapp'])->name('billing.whatsapp');
        Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
        Route::get('/appointments/{appointment}', [AppointmentController::class, 'show'])->name('appointments.show');
        Route::get('/attendance', [AdminAttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance', [AdminAttendanceController::class, 'update'])->name('attendance.update');
        Route::resource('customers', AdminCustomerController::class)->only(['index', 'create', 'store', 'show']);
        Route::resource('services', AdminServiceController::class)->except(['show', 'destroy']);
        Route::patch('/services/{service}/favorite', [AdminServiceController::class, 'toggleFavorite'])->name('services.favorite.toggle');
        Route::get('/settings', [AdminSettingController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [AdminSettingController::class, 'update'])->name('settings.update');
        Route::patch('/services/{service}/toggle', [AdminServiceController::class, 'toggle'])->name('services.toggle');
        Route::delete('/services/{service}/images/{image}', [AdminServiceController::class, 'destroyImage'])->name('services.images.destroy');
        Route::resource('staff', AdminStaffController::class)->only(['index', 'create', 'store']);
        Route::get('/staff/{staff}/edit-password', [StaffPasswordController::class, 'edit'])->name('staff.edit-password');
        Route::put('/staff/{staff}/password', [StaffPasswordController::class, 'update'])->name('staff.update-password');
    });

Route::prefix('staff')
    ->name('staff.')
    ->middleware(['auth', 'active', 'password.changed', 'role:staff'])
    ->group(function (): void {
        Route::get('/dashboard', StaffDashboardController::class)->name('dashboard');
        Route::get('/search', SearchController::class)->name('search');
        Route::get('/billing/create', [BillingController::class, 'create'])->name('billing.create');
        Route::post('/billing', [BillingController::class, 'store'])->name('billing.store');
        Route::get('/billing/customer-lookup', [BillingController::class, 'lookupCustomer'])->name('billing.customer-lookup');
        Route::get('/billing/{bill}/success', [BillingController::class, 'success'])->name('billing.success');
        Route::get('/billing/{bill}/complete', [BillingController::class, 'success'])->name('billing.complete');
        Route::get('/billing/{bill}', [BillingController::class, 'show'])->name('billing.show');
        Route::get('/billing/{bill}/print', [BillingController::class, 'print'])->name('billing.print');
        Route::get('/billing/{bill}/pdf', [BillingController::class, 'pdf'])->name('billing.pdf');
        Route::get('/billing/{bill}/whatsapp', [BillingController::class, 'whatsapp'])->name('billing.whatsapp');
    });

require __DIR__.'/auth.php';
