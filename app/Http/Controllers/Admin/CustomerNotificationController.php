<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerNotificationCampaign;
use App\Models\CustomerPushSubscription;
use App\Services\FirebaseCloudMessaging;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerNotificationController extends Controller
{
    private const DESTINATIONS = [
        'home' => '/',
        'services' => '/services',
        'gallery' => '/gallery',
        'about' => '/about',
        'contact' => '/contact',
        'book-appointment' => '/book-appointment',
    ];

    public function index(): View
    {
        return view('admin.customer-notifications.index', [
            'campaigns' => CustomerNotificationCampaign::query()
                ->with('creator:id,name')
                ->latest()
                ->paginate(15),
            'subscriptionCount' => CustomerPushSubscription::query()->whereNull('revoked_at')->count(),
            'destinations' => self::DESTINATIONS,
            'notificationsAvailable' => app(FirebaseCloudMessaging::class)->isWebPushConfigured(),
        ]);
    }

    public function store(Request $request, FirebaseCloudMessaging $messaging): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:500'],
            'destination' => ['required', 'string', 'in:'.implode(',', array_keys(self::DESTINATIONS))],
        ]);

        abort_unless($messaging->isWebPushConfigured(), 503, 'Customer notifications are not configured.');

        $actionUrl = self::DESTINATIONS[$validated['destination']];
        $campaign = CustomerNotificationCampaign::query()->create([
            'created_by' => $request->user()->id,
            'title' => $validated['title'],
            'body' => $validated['body'],
            'action_url' => self::DESTINATIONS[$validated['destination']],
        ]);

        $recipientCount = 0;
        $sentCount = 0;
        $failedCount = 0;

        CustomerPushSubscription::query()
            ->whereNull('revoked_at')
            ->orderBy('id')
            ->chunkById(100, function ($subscriptions) use (
                $messaging,
                $validated,
                $actionUrl,
                $campaign,
                &$recipientCount,
                &$sentCount,
                &$failedCount,
            ): void {
                foreach ($subscriptions as $subscription) {
                    $recipientCount++;
                    $result = $messaging->sendToToken(
                        $subscription->token,
                        $validated['title'],
                        $validated['body'],
                        $actionUrl,
                    );

                    if ($result['sent']) {
                        $sentCount++;
                        $subscription->forceFill(['last_seen_at' => now()])->save();
                    } else {
                        $failedCount++;
                        if ($result['invalid']) {
                            $subscription->forceFill(['token' => '', 'revoked_at' => now()])->save();
                        }
                    }
                }

                $campaign->forceFill([
                    'recipient_count' => $recipientCount,
                    'sent_count' => $sentCount,
                    'failed_count' => $failedCount,
                ])->save();
            });

        $campaign->forceFill([
            'recipient_count' => $recipientCount,
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'sent_at' => now(),
        ])->save();

        return redirect()
            ->route('admin.customer-notifications.index')
            ->with('status', "Notification sent to {$sentCount} of {$recipientCount} subscribed devices.");
    }
}
