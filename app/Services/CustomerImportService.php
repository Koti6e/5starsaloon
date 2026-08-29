<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerImportService
{
    /**
     * @return array{total:int, imported:int, skipped:int, duplicates:int, errors:int, messages:array<int, string>}
     */
    public function import(string $path, bool $dryRun = false): array
    {
        if (! is_readable($path)) {
            return $this->emptyResult(["Import source is not readable: {$path}"], 1);
        }

        $records = $this->parse(file_get_contents($path) ?: '');
        $result = ['total' => count($records), 'imported' => 0, 'skipped' => 0, 'duplicates' => 0, 'errors' => 0, 'messages' => []];
        $seen = [];

        foreach ($records as $index => $record) {
            $lineNumber = $record['line'];
            $mobile = Customer::normalizeMobile($record['mobile']);

            if (! preg_match('/^[6-9]\d{9}$/', $mobile)) {
                $result['skipped']++;
                $result['errors']++;
                $result['messages'][] = "Line {$lineNumber}: skipped invalid mobile '{$record['mobile']}'.";
                continue;
            }

            $name = $this->normalizeName($record['name'], $mobile);
            $seenKey = $seen[$mobile] ?? null;
            if ($seenKey && ! $this->sameIdentity($seenKey, $name)) {
                $result['duplicates']++;
                $result['skipped']++;
                $result['messages'][] = "Line {$lineNumber}: duplicate mobile {$mobile} has ambiguous names '{$seenKey}' and '{$name}'.";
                continue;
            }
            $seen[$mobile] = $name;

            $existing = Customer::withTrashed()->where('mobile', $mobile)->first();
            if ($existing) {
                $result['duplicates']++;
                if (! $dryRun && ! $existing->trashed()) {
                    $existing->forceFill([
                        'membership_id' => $existing->membership_id ?: $record['membership_id'],
                        'branch' => $existing->branch ?: $record['branch'],
                        'gender' => $existing->gender ?: $record['gender'],
                        'date_of_birth' => $existing->date_of_birth ?: $record['date_of_birth'],
                        'whatsapp_status' => $existing->whatsapp_status ?: 'not_contacted',
                        'source' => $existing->source ?: 'customer_list_pdf',
                        'source_reference' => $existing->source_reference ?: 'Customer List PDF',
                    ])->save();
                }
                continue;
            }

            if ($dryRun) {
                $result['imported']++;
                continue;
            }

            DB::transaction(function () use ($record, $mobile, $name): void {
                Customer::query()->create([
                    'customer_code' => (new CustomerCodeGenerator)->generate(),
                    'name' => $name,
                    'mobile' => $mobile,
                    'membership_id' => $record['membership_id'],
                    'branch' => $record['branch'],
                    'gender' => $record['gender'],
                    'date_of_birth' => $record['date_of_birth'],
                    'anniversary_date' => null,
                    'total_visits' => 0,
                    'total_spent' => '0.00',
                    'last_visit_at' => null,
                    'status' => 'active',
                    'whatsapp_status' => 'not_contacted',
                    'source' => 'customer_list_pdf',
                    'source_reference' => 'Customer List PDF',
                    'imported_at' => now('Asia/Kolkata'),
                ]);
            });

            $result['imported']++;
        }

        return $result;
    }

    /**
     * @return array<int, array{line:int, branch:string, name:string, mobile:string, membership_id:?string, gender:?string, date_of_birth:?string}>
     */
    public function parse(string $text): array
    {
        $records = [];

        foreach (preg_split('/\R/', str_replace("\f", "\n", $text)) ?: [] as $lineIndex => $line) {
            $line = trim(preg_replace('/\s+/', ' ', $line) ?: '');
            if ($line === '' || str_contains($line, 'Customer List') || str_starts_with($line, 'Image Branch')) {
                continue;
            }

            if (! preg_match('/\b(Vandalur)\b\s+(.+)/i', $line, $matches)) {
                continue;
            }

            $branch = Str::title(Str::lower($matches[1]));
            $rest = $matches[2];
            if (! preg_match('/(.+?)\s+(\d{9,13})(.*)$/', $rest, $parts)) {
                continue;
            }

            $tail = trim($parts[3]);
            $gender = null;
            if (preg_match('/\b(Male|Female|Other)\b/i', $tail, $genderMatch)) {
                $gender = Str::lower($genderMatch[1]);
            }

            $membership = trim(preg_replace('/\b(Male|Female|Other)\b/i', '', preg_replace('/\b\d{2}\/\d{2}\/\d{4}\b/', '', $tail) ?: '') ?: '');
            $membership = in_array(Str::lower($membership), ['', '0', 'id'], true) ? null : $membership;

            $records[] = [
                'line' => $lineIndex + 1,
                'branch' => $branch,
                'name' => $parts[1],
                'mobile' => $parts[2],
                'membership_id' => $membership,
                'gender' => $gender,
                'date_of_birth' => $this->validDate($tail),
            ];
        }

        return $records;
    }

    private function normalizeName(string $name, string $mobile): string
    {
        $name = trim(preg_replace('/[^A-Za-z .-]+/', ' ', $name) ?: '');
        $name = trim(preg_replace('/\s+/', ' ', $name) ?: '');

        if ($name === '' || Str::lower($name) === '0') {
            return 'Customer '.substr($mobile, -4);
        }

        return Str::title(Str::lower($name));
    }

    private function validDate(string $tail): ?string
    {
        if (! preg_match_all('/\b\d{2}\/\d{2}\/\d{4}\b/', $tail, $matches)) {
            return null;
        }

        foreach ($matches[0] as $date) {
            if ($date === '01/01/1900') {
                continue;
            }

            try {
                return Carbon::createFromFormat('d/m/Y', $date, 'Asia/Kolkata')->toDateString();
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    private function sameIdentity(string $first, string $second): bool
    {
        return Str::lower(trim($first)) === Str::lower(trim($second));
    }

    /**
     * @return array{total:int, imported:int, skipped:int, duplicates:int, errors:int, messages:array<int, string>}
     */
    private function emptyResult(array $messages, int $errors = 0): array
    {
        return ['total' => 0, 'imported' => 0, 'skipped' => 0, 'duplicates' => 0, 'errors' => $errors, 'messages' => $messages];
    }
}
