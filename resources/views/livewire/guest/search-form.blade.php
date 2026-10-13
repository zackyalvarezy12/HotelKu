<form wire:submit="search" class="{{ $compact ? 'card p-3' : 'card p-4 shadow-lg sm:p-5' }}">
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-12">
        {{-- Kota --}}
        <div class="lg:col-span-4">
            <label for="search-city" class="form-label">Kota</label>
            <select id="search-city" wire:model="city"
                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Semua kota</option>
                @foreach ($cities as $option)
                    <option value="{{ $option }}">{{ $option }}</option>
                @endforeach
            </select>
            @error('city') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Check-in --}}
        <div class="lg:col-span-3">
            <label for="search-checkin" class="form-label">Check-in</label>
            <input id="search-checkin" type="date" wire:model="checkIn" min="{{ now()->toDateString() }}"
                   class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @error('checkIn') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Check-out --}}
        <div class="lg:col-span-3">
            <label for="search-checkout" class="form-label">Check-out</label>
            <input id="search-checkout" type="date" wire:model="checkOut" min="{{ now()->addDay()->toDateString() }}"
                   class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @error('checkOut') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Tamu --}}
        <div class="sm:col-span-2 lg:col-span-2">
            <label for="search-guests" class="form-label">Tamu</label>
            <select id="search-guests" wire:model="guests"
                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach (range(1, 10) as $n)
                    <option value="{{ $n }}">{{ $n }} orang</option>
                @endforeach
            </select>
            @error('guests') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-xs text-gray-500">
            Kosongkan tanggal untuk melihat semua hotel di kota tersebut.
        </p>
        <button type="submit" class="btn-primary w-full sm:w-auto" wire:loading.attr="disabled">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z" />
            </svg>
            <span wire:loading.remove>Cari Hotel</span>
            <span wire:loading>Mencari…</span>
        </button>
    </div>
</form>
