<x-app-layout>
    <x-slot name="header">
        @php
            $hour = now('Asia/Kolkata')->hour;
            $greeting = match (true) {
                $hour >= 5 && $hour < 12 => 'Good Morning',
                $hour >= 12 && $hour < 17 => 'Good Afternoon',
                $hour >= 17 && $hour < 21 => 'Good Evening',
                default => 'Good Night',
            };
        @endphp
        <div class="flex flex-col gap-1">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--app-subtle)]">{{ $greeting }}</p>
            <h1 class="font-serif text-2xl text-[var(--app-primary)] sm:text-3xl">Welcome back, {{ auth()->user()->name }} 👋</h1>
            <p class="text-sm text-[var(--app-muted)]">Here's how your salon is performing today.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                @foreach ([
                    ['Today\'s Sales', \App\Support\Money::inr($todaySales), route('admin.billing.create')],
                    ['Today\'s Bills', $todayBills, route('admin.billing.create')],
                    ['Today\'s Appointments', $todayAppointments, route('admin.dashboard')],
                    ['Staff Present Today', $staffPresentToday, route('admin.attendance.index')],
                    ['Staff Absent Today', $staffAbsentToday, route('admin.attendance.index')],
                ] as [$label, $value, $href])
                    <x-admin.card class="h-full transition hover:border-[#f4d27a]">
                        <a href="{{ $href }}" class="block">
                            <p class="text-sm text-[#d8c8a3]">{{ $label }}</p>
                            <p class="mt-2 text-3xl font-semibold text-[#fff9ea]">{{ $value }}</p>
                        </a>
                    </x-admin.card>
                @endforeach
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['Cash', \App\Support\Money::inr($cashSales)],
                    ['UPI', \App\Support\Money::inr($upiSales)],
                    ['Card', \App\Support\Money::inr($cardSales)],
                    ['Pending Elite Home Visits', $pendingHomeVisits],
                ] as [$label, $value])
                    <x-admin.card class="h-full">
                        <p class="text-sm text-[#d8c8a3]">{{ $label }}</p>
                        <p class="mt-2 text-2xl font-semibold text-[#fff9ea]">{{ $value }}</p>
                    </x-admin.card>
                @endforeach
            </div>

            <x-admin.card>
                <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h2 class="font-serif text-2xl text-[#f4d27a]">Quick Actions</h2>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-4">
                        <a href="{{ route('admin.billing.create') }}" class="rounded-md bg-[#d5a93b] px-4 py-3 text-center text-sm font-semibold text-[#111]">Quick Billing</a>
                        <a href="{{ route('appointments.book') }}" class="rounded-md border border-[#c8a24a]/40 px-4 py-3 text-center text-sm font-semibold text-[#f8efd8]">Add Appointment</a>
                        <a href="{{ route('admin.customers.create') }}" class="rounded-md border border-[#c8a24a]/40 px-4 py-3 text-center text-sm font-semibold text-[#f8efd8]">Add Customer</a>
                        <a href="{{ route('admin.attendance.index') }}" class="rounded-md border border-[#c8a24a]/40 px-4 py-3 text-center text-sm font-semibold text-[#f8efd8]">Mark Attendance</a>
                    </div>
                </div>
            </x-admin.card>

            <x-admin.card>
                <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <h2 class="font-serif text-2xl text-[#f4d27a]">Today's Attendance</h2>
                    <a href="{{ route('admin.attendance.index') }}" class="text-sm font-semibold text-[#f4d27a]">Manage attendance</a>
                </div>
                <div class="mt-5 grid gap-3 md:hidden">
                    @foreach ($attendanceRows as $row)
                        @php($status = $row['attendance']?->status ?? 'not_marked')
                        <div class="rounded-md border border-[#c8a24a]/15 bg-black p-4">
                            <div class="flex items-center justify-between">
                                <p class="font-semibold text-[#fff9ea]">{{ $row['staff']->name }}</p>
                                <span class="text-xs uppercase text-[#a89567]">{{ str_replace('_', ' ', $status) }}</span>
                            </div>
                            <p class="mt-2 text-xs text-[#d8c8a3]">{{ $row['staff']->shift_start ?: 'Shift not set' }} - {{ $row['staff']->shift_end ?: 'Shift not set' }}</p>
                        </div>
                    @endforeach
                </div>
                <div class="mt-5 hidden overflow-x-auto md:block">
                    <table class="min-w-full divide-y divide-[#c8a24a]/15 text-sm">
                        <thead class="text-left text-[#f4d27a]"><tr><th class="py-3">Staff</th><th>Shift</th><th>Status</th><th>Check in</th><th>Check out</th></tr></thead>
                        <tbody class="divide-y divide-[#c8a24a]/10 text-[#f8efd8]">
                            @foreach ($attendanceRows as $row)
                                @php($attendance = $row['attendance'])
                                <tr>
                                    <td class="py-3">{{ $row['staff']->name }}</td>
                                    <td>{{ $row['staff']->shift_start ?: 'Not set' }} - {{ $row['staff']->shift_end ?: 'Not set' }}</td>
                                    <td>{{ str_replace('_', ' ', $attendance?->status ?? 'not_marked') }}</td>
                                    <td>{{ $attendance?->check_in_time ?: '-' }}</td>
                                    <td>{{ $attendance?->check_out_time ?: '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-admin.card>
        </div>
    </div>
</x-app-layout>
