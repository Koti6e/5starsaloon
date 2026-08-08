@props([])

@if (session('status') || session('success') || session('error') || $errors->any())
    <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
        <div class="space-y-2">
            @if (session('status'))
                <div class="rounded-md border border-[#c8a24a]/20 bg-[#11100d] p-3 text-sm text-[#fff9ea]">{{ session('status') }}</div>
            @endif
            @if (session('success'))
                <div class="rounded-md border border-[#c8a24a]/20 bg-[#0b3710] p-3 text-sm text-[#d8ffd9]">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-md border border-red-700 bg-[#2b0b0b] p-3 text-sm text-red-300">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md border border-[#c8a24a]/20 bg-[#11100d] p-3 text-sm text-[#fff9ea]">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
@endif
