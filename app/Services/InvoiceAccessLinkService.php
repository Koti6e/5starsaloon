<?php

namespace App\Services;

use App\Models\Bill;
use RuntimeException;

class InvoiceAccessLinkService
{
    public function issue(Bill $bill): string
    {
        $configuredUrl = (string) config('app.url');
        $parts = parse_url($configuredUrl);
        $scheme = $parts['scheme'] ?? null;
        $host = $parts['host'] ?? null;
        if (! $host || ! in_array($scheme, ['http', 'https'], true)
            || (app()->environment('production') && $scheme !== 'https')
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
            throw new RuntimeException('A configured HTTPS application URL is required for customer invoice links.');
        }

        $token = bin2hex(random_bytes(32));
        $bill->forceFill([
            'invoice_access_token_hash' => hash('sha256', $token),
            'invoice_access_expires_at' => now()->addDays(90),
            'invoice_access_revoked_at' => null,
        ])->save();

        return rtrim($configuredUrl, '/').route('invoice.public', $token, false);
    }
}
