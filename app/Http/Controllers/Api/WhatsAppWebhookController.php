<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class WhatsAppWebhookController extends Controller
{
    public function webhook(Request $request)
    {
        // Meta webhook verification
        if ($request->isMethod('GET')) {
            $mode = $request->query('hub_mode');
            $token = $request->query('hub_verify_token');
            $challenge = $request->query('hub_challenge');

            if (
                $mode === 'subscribe' &&
                hash_equals((string) config('services.whatsapp.verify_token'), (string) $token)
            ) {
                return response($challenge, 200)
                    ->header('Content-Type', 'text/plain');
            }

            return response('Forbidden', 403);
        }

        // Incoming WhatsApp events
        $payload = $request->all();

        Log::warning('WhatsApp webhook received', [
            'payload' => $payload,
        ]);

        // Always acknowledge Meta quickly.
        return response()->json(['status' => 'ok'], 200);
    }
}
