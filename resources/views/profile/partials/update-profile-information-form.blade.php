<section>
    <header>
        <h2 class="text-lg font-medium text-[#fff9ea]">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-[#d8c8a3]">
            {{ __("Update your account's profile information.") }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div class="flex items-center gap-4">
            @if ($user->profilePhotoUrl())
                <img src="{{ $user->profilePhotoUrl() }}" alt="{{ $user->name }}" class="h-16 w-16 rounded-full border border-[#c8a24a]/40 object-cover">
            @else
                <div class="flex h-16 w-16 items-center justify-center rounded-full border border-[#c8a24a]/40 bg-[#d5a93b] text-lg font-bold text-black">
                    {{ $user->initials() }}
                </div>
            @endif
            <div class="flex-1">
                <x-input-label for="profile_photo" :value="__('Profile Photo')" class="text-[#f8efd8]" />
                <input id="profile_photo" name="profile_photo" type="file" accept="image/png,image/jpeg,image/webp" class="mt-1 block w-full rounded-md border border-[#c8a24a]/30 bg-black px-3 py-2 text-sm text-[#fff9ea] file:mr-4 file:rounded-md file:border-0 file:bg-[#d5a93b] file:px-3 file:py-2 file:text-sm file:font-semibold file:text-black">
                <x-input-error class="mt-2" :messages="$errors->get('profile_photo')" />
            </div>
        </div>

        <div>
            <x-input-label for="name" :value="__('Name')" class="text-[#f8efd8]" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full border-[#c8a24a]/30 bg-black text-[#fff9ea]" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" class="text-[#f8efd8]" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full border-[#c8a24a]/30 bg-black text-[#fff9ea]" :value="old('email', $user->email)" autocomplete="email" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button class="bg-[#d5a93b] text-[#111]">{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-[#d8c8a3]"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
