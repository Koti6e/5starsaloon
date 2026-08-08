<?php

namespace App\Services;

use App\Models\Appointment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class AppointmentNumberGenerator
{
    /**
     * Generate a concurrency-safe appointment order number in format: 5star/App/YYYY/MM/001
     *
     * @throws RuntimeException
     */
    public function generate(): string
    {
        $maxAttempts = 5;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return DB::transaction(function (): string {
                    $now = now('Asia/Kolkata');
                    $period = $now->format('Y/m');
                    $stem = '5star/App/'.$period.'/';

                    $latestNumber = Appointment::query()
                        ->where('booking_number', 'like', $stem.'%')
                        ->lockForUpdate()
                        ->orderByDesc('booking_number')
                        ->value('booking_number');

                    $next = 1;
                    if ($latestNumber && str_contains($latestNumber, '/')) {
                        $lastSeq = (int) Str::afterLast($latestNumber, '/');
                        if ($lastSeq > 0) {
                            $next = $lastSeq + 1;
                        }
                    }

                    return $stem.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
                });
            } catch (Throwable $exception) {
                if ($attempt === $maxAttempts) {
                    throw new RuntimeException(
                        "Appointment order number generation failed after {$maxAttempts} attempts: {$exception->getMessage()}",
                        0,
                        $exception
                    );
                }

                usleep(50000 * $attempt);
            }
        }

        throw new RuntimeException("Appointment order number generation failed after {$maxAttempts} attempts.");
    }
}
