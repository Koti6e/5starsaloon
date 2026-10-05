<?php

namespace App\Http\Controllers;

use App\Models\CustomerPushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerPushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'min:50', 'max:4096'],
            'platform' => ['nullable', 'string', 'max:30'],
        ]);

        CustomerPushSubscription::query()->updateOrCreate(
            ['token_hash' => CustomerPushSubscription::hashToken($validated['token'])],
            [
                'token' => $validated['token'],
                'platform' => $validated['platform'] ?? 'web',
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                'consented_at' => now(),
                'last_seen_at' => now(),
                'revoked_at' => null,
            ],
        );

        return response()->json(['ok' => true], 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'min:50', 'max:4096'],
        ]);

        $subscription = CustomerPushSubscription::query()
            ->where('token_hash', CustomerPushSubscription::hashToken($validated['token']))
            ->whereNull('revoked_at')
            ->first();

        $subscription?->forceFill(['token' => '', 'revoked_at' => now()])->save();

        return response()->json(['ok' => true]);
    }
}
