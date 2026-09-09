<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WhatsAppWebhookController;

Route::match(['GET', 'POST'], '/whatsapp/webhook', [WhatsAppWebhookController::class, 'webhook']);
