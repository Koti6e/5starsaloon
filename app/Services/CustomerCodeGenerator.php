<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class CustomerCodeGenerator
{
    /**
     * Generate a concurrency-safe customer code in format: CUS-YYYY-XXXXXX
     *
     * @throws RuntimeException
     */
    public function generate(): string
    {
        $maxAttempts = 5;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return DB::transaction(function (): string {
                    $year = now('Asia/Kolkata')->format('Y');
                    $prefix = "CUS-{$year}-";

                    $latestCode = Customer::withTrashed()
                        ->where('customer_code', 'like', $prefix.'%')
                        ->lockForUpdate()
                        ->orderByDesc('customer_code')
                        ->value('customer_code');

                    if ($latestCode && preg_match('/-(\d+)$/', $latestCode, $matches)) {
                        $nextNumber = ((int) $matches[1]) + 1;
                    } else {
                        $maxId = Customer::withTrashed()->max('id') ?? 0;
                        $nextNumber = $maxId + 1;
                    }

                    return $prefix.str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
                });
            } catch (Throwable $exception) {
                if ($attempt === $maxAttempts) {
                    throw new RuntimeException(
                        "Customer code generation failed after {$maxAttempts} attempts: {$exception->getMessage()}",
                        0,
                        $exception
                    );
                }

                usleep(50000 * $attempt);
            }
        }

        throw new RuntimeException("Customer code generation failed after {$maxAttempts} attempts.");
    }
}
