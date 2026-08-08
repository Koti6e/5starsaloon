<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\SalonSetting;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AboutSalonOsController extends Controller
{
    public function __invoke(): View
    {
        $settings = SalonSetting::cached();
        $logoPath = $settings['logo'] ?? 'images/brand/logo-small.webp';
        $hasLogo = filled($logoPath) && file_exists(public_path($logoPath));
        $hasPromotion = filter_var($settings['promotion_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN)
            && filled($settings['promotion_title'] ?? null)
            && filled($settings['promotion_button_link'] ?? null);

        $checks = collect([
            $this->check('Salon Logo Configured', $hasLogo, 'admin.settings.edit'),
            $this->check('Salon Name Configured', filled($settings['salon_name'] ?? null), 'admin.settings.edit'),
            $this->check('Business Address Configured', filled($settings['address'] ?? null) && ($settings['address'] ?? null) !== 'Visit the salon for location details.', 'admin.settings.edit'),
            $this->check('Phone Number Configured', filled($settings['primary_phone'] ?? null), 'admin.settings.edit'),
            $this->check('WhatsApp Number Configured', filled($settings['whatsapp_number'] ?? null), 'admin.settings.edit'),
            $this->check('Working Hours Configured', filled($settings['working_hours'] ?? null) && ($settings['working_hours'] ?? null) !== 'Open daily by appointment.', 'admin.settings.edit'),
            $this->check('Weekly Holiday Configured', filled($settings['weekly_holiday'] ?? null) && ($settings['weekly_holiday'] ?? null) !== 'Confirmed by the salon team.', 'admin.settings.edit'),
            $this->check('Active Services Available', Service::query()->where('status', 'active')->exists(), 'admin.services.index'),
            $this->check('Smart Saver Packages Available', Service::query()->where('is_package', true)->where('status', 'active')->exists(), 'admin.services.index'),
            $this->check('Active Admin Account Available', User::query()->where('role', 'admin')->where('status', 'active')->exists(), 'admin.staff.index'),
            $this->check('Active Staff Account Available', User::query()->where('role', 'staff')->where('status', 'active')->exists(), 'admin.staff.index'),
            $this->check('Invoice Prefix Configured', filled($settings['invoice_prefix'] ?? null), 'admin.settings.edit'),
            $this->check('Invoice Footer Configured', filled($settings['invoice_footer_text'] ?? null), 'admin.settings.edit'),
            $this->check('Promotion Banner Configured', $hasPromotion, 'admin.settings.edit'),
            $this->check('Google Maps Link Configured', filled($settings['google_maps_url'] ?? null), 'admin.settings.edit'),
            $this->check('WhatsApp Floater Enabled', filter_var($settings['whatsapp_floater_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN), 'admin.settings.edit'),
            $this->check('PDF Invoice Verified', Bill::query()->exists(), 'admin.billing.create'),
            $this->check('Backup Verified', false, null, 'Manual production backup verification required.'),
        ]);

        $completed = $checks->where('ready', true)->count();

        return view('admin.about-salonos', [
            'settings' => $settings,
            'checks' => $checks,
            'completed' => $completed,
            'total' => $checks->count(),
            'percentage' => (int) round(($completed / max(1, $checks->count())) * 100),
            'databaseDriver' => config('database.default'),
            'hasAppointmentsTable' => Schema::hasTable('appointments'),
        ]);
    }

    private function check(string $label, bool $ready, ?string $route, ?string $note = null): array
    {
        return [
            'label' => $label,
            'ready' => $ready,
            'route' => $route,
            'note' => $note,
        ];
    }
}
