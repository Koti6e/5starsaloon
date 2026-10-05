<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--app-subtle)]">Customer communications</p>
            <h1 class="font-serif text-2xl text-[var(--app-primary)] sm:text-3xl">Push notifications</h1>
            <p class="text-sm text-[var(--app-muted)]">Send updates only to customers who opted in on their device.</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        <x-admin.card>
            <h2 class="text-lg font-semibold text-[var(--app-primary)]">Send a customer update</h2>
            <p class="mt-1 text-sm text-[var(--app-muted)]">{{ number_format($subscriptionCount) }} active device subscription(s)</p>
            @unless ($notificationsAvailable)
                <p role="alert" class="mt-3 rounded-lg border border-amber-400/30 bg-amber-400/10 p-3 text-sm text-amber-200">Firebase Web Push is not fully configured. No notification can be sent until its server credentials and web-push settings are configured.</p>
            @endunless

            <form method="POST" action="{{ route('admin.customer-notifications.store') }}" class="mt-5 grid gap-4">
                @csrf
                <fieldset @disabled(! $notificationsAvailable) class="grid gap-4">
                <div>
                    <label for="notification-title" class="mb-1 block text-sm font-medium">Title</label>
                    <input id="notification-title" name="title" type="text" maxlength="120" required value="{{ old('title') }}" class="w-full rounded-lg border border-[var(--app-border)] bg-[var(--app-bg)] px-3 py-2 text-[var(--app-text)]">
                    @error('title') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="notification-body" class="mb-1 block text-sm font-medium">Message</label>
                    <textarea id="notification-body" name="body" maxlength="500" rows="3" required class="w-full rounded-lg border border-[var(--app-border)] bg-[var(--app-bg)] px-3 py-2 text-[var(--app-text)]">{{ old('body') }}</textarea>
                    @error('body') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="notification-destination" class="mb-1 block text-sm font-medium">Open this salon page when tapped</label>
                    <select id="notification-destination" name="destination" required class="w-full rounded-lg border border-[var(--app-border)] bg-[var(--app-bg)] px-3 py-2 text-[var(--app-text)]">
                        @foreach ($destinations as $key => $path)
                            <option value="{{ $key }}" @selected(old('destination', 'book-appointment') === $key)>{{ ucfirst(str_replace('-', ' ', $key)) }}</option>
                        @endforeach
                    </select>
                    @error('destination') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <button type="submit" class="rounded-lg bg-[var(--app-primary)] px-5 py-2.5 font-semibold text-[var(--on-accent)]" onclick="return confirm('Send this update to all opted-in customer devices?')">Send notification</button>
                </div>
                </fieldset>
            </form>
        </x-admin.card>

        <x-admin.card>
            <h2 class="text-lg font-semibold text-[var(--app-primary)]">Recent campaigns</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-[var(--app-border)] text-left text-sm">
                    <thead class="text-[var(--app-muted)]">
                        <tr>
                            <th class="py-3 pr-4">Sent</th>
                            <th class="py-3 pr-4">Message</th>
                            <th class="py-3 pr-4">Destination</th>
                            <th class="py-3 pr-4">Delivery</th>
                            <th class="py-3">Created by</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--app-border)]">
                        @forelse ($campaigns as $campaign)
                            <tr>
                                <td class="whitespace-nowrap py-3 pr-4">{{ $campaign->sent_at?->timezone('Asia/Kolkata')->format('d M Y, h:i A') ?? 'In progress' }}</td>
                                <td class="min-w-56 py-3 pr-4">
                                    <p class="font-semibold">{{ $campaign->title }}</p>
                                    <p class="mt-1 text-[var(--app-muted)]">{{ $campaign->body }}</p>
                                </td>
                                <td class="py-3 pr-4"><a class="underline" href="{{ $campaign->action_url }}">{{ $campaign->action_url }}</a></td>
                                <td class="whitespace-nowrap py-3 pr-4">{{ $campaign->sent_count }} / {{ $campaign->recipient_count }} sent ({{ $campaign->failed_count }} failed)</td>
                                <td class="py-3">{{ $campaign->creator?->name ?? 'Deleted admin' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-6 text-center text-[var(--app-muted)]">No customer campaigns have been sent.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $campaigns->links() }}</div>
        </x-admin.card>
    </div>
</x-app-layout>
