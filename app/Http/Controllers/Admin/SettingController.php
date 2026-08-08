<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalonSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.edit', [
            'settings' => SalonSetting::cached(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'salon_name' => ['required', 'string', 'max:120'],
            'tagline' => ['nullable', 'string', 'max:180'],
            'address' => ['nullable', 'string', 'max:500'],
            'area' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'pincode' => ['nullable', 'string', 'max:12'],
            'primary_phone' => ['nullable', 'string', 'max:30'],
            'alternate_phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:160'],
            'google_maps_url' => ['nullable', 'url', 'max:500'],
            'working_hours' => ['nullable', 'string', 'max:160'],
            'weekly_holiday' => ['nullable', 'string', 'max:120'],
            'instagram_url' => ['nullable', 'url', 'max:500'],
            'facebook_url' => ['nullable', 'url', 'max:500'],
            'youtube_url' => ['nullable', 'url', 'max:500'],
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
            'whatsapp_floater_enabled' => ['nullable', 'boolean'],
            'whatsapp_default_message' => ['nullable', 'string', 'max:300'],
            'default_theme' => ['required', Rule::in(['emerald', 'sapphire', 'crimson', 'gold', 'pearl', 'obsidian', 'light', 'dark'])],
            'invoice_prefix' => ['required', 'string', 'max:20'],
            'invoice_footer_text' => ['nullable', 'string', 'max:500'],
            'invoice_thank_you_message' => ['nullable', 'string', 'max:300'],
            'promotion_enabled' => ['nullable', 'boolean'],
            'promotion_title' => ['nullable', 'string', 'max:160'],
            'promotion_subtitle' => ['nullable', 'string', 'max:220'],
            'promotion_offer_price' => ['nullable', 'string', 'max:80'],
            'promotion_start_date' => ['nullable', 'date'],
            'promotion_end_date' => ['nullable', 'date', 'after_or_equal:promotion_start_date'],
            'promotion_button_text' => ['nullable', 'string', 'max:60'],
            'promotion_button_link' => ['nullable', 'string', 'max:500'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,ico', 'max:2048'],
            'favicon' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,ico', 'max:512'],
            'promotion_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:3072'],
        ]);

        foreach (['whatsapp_floater_enabled', 'promotion_enabled'] as $flag) {
            $validated[$flag] = $request->boolean($flag) ? '1' : '0';
        }

        $validated['default_theme'] = match ($validated['default_theme']) {
            'light' => 'pearl',
            'dark' => 'obsidian',
            default => $validated['default_theme'],
        };

        foreach (['logo', 'favicon', 'promotion_image'] as $fileKey) {
            unset($validated[$fileKey]);
            if ($request->hasFile($fileKey)) {
                $validated[$fileKey] = $this->storeImage($request, $fileKey);
            }
        }

        foreach ($validated as $key => $value) {
            SalonSetting::putValue($key, $value);
        }

        return back()->with('status', 'Settings saved.');
    }

    private function storeImage(Request $request, string $key): string
    {
        File::ensureDirectoryExists(public_path('images/settings'));
        $file = $request->file($key);
        $name = $key.'-'.now('Asia/Kolkata')->format('YmdHis').'.'.$file->extension();
        $file->move(public_path('images/settings'), $name);

        return 'images/settings/'.$name;
    }
}
