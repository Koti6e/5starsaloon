<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalonSetting;
use App\Support\WorkingHours;
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
            'google_place_id' => ['nullable', 'string', 'max:200'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
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
            'weekly_working_hours' => ['sometimes', 'array'],
        ]);

        if (array_key_exists('weekly_working_hours', $validated)) {
            foreach (WorkingHours::DAYS as $day) {
                $validated['weekly_working_hours'][$day] = validator(
                    ['row' => $validated['weekly_working_hours'][$day] ?? []],
                    ['row' => ['required', 'array'], 'row.open' => ['nullable', 'boolean'], 'row.opens' => ['nullable', 'date_format:H:i'], 'row.closes' => ['nullable', 'date_format:H:i']],
                )->validate()['row'];
                $row = $validated['weekly_working_hours'][$day];
                if (! empty($row['open']) && (empty($row['opens']) || empty($row['closes']))) {
                    return back()->withErrors(["weekly_working_hours.{$day}.opens" => "Add opening and closing times for {$day}, or mark it closed."])->withInput();
                }
                if (! empty($row['open']) && $row['opens'] === $row['closes']) {
                    return back()->withErrors(["weekly_working_hours.{$day}.closes" => "Opening and closing times must be different for {$day}."])->withInput();
                }
                $validated['weekly_working_hours'][$day]['open'] = ! empty($row['open']);
            }
            $validated['weekly_working_hours'] = json_encode($validated['weekly_working_hours'], JSON_THROW_ON_ERROR);
        }

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
