<?php

namespace App\Http\Controllers;

use App\Models\FcmDeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushDeviceController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'token' => ['required', 'string', 'min:50'],
            'platform' => ['nullable', 'string', 'max:30'],
        ]);

        FcmDeviceToken::query()->updateOrCreate(
            ['token_hash' => FcmDeviceToken::hashToken($validated['token'])],
            [
                'user_id' => $request->user()->id,
                'token' => $validated['token'],
                'platform' => $validated['platform'] ?? 'web',
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                'last_seen_at' => now(),
                'revoked_at' => null,
            ],
        );

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'min:50'],
        ]);

        FcmDeviceToken::query()
            ->where('user_id', $request->user()->id)
            ->where('token_hash', FcmDeviceToken::hashToken($validated['token']))
            ->update(['revoked_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
