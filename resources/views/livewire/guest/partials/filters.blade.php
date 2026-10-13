@php
    /** @var \Illuminate\Support\Collection $cityOptions */
    /** @var \Illuminate\Support\Collection $facilityOptions */
@endphp

<div class="space-y-5 text-sm">
    <div class="flex items-center justify-between">
        <h2 class="text-sm font-semibold text-gray-900">Filter</h2>
        @if ($this->activeFilterCount() > 0)
            <button type="button" wire:click="resetFilters" class="text-xs font-medium text-indigo-600 hover:underline">
                Hapus semua
            </button>
        @endif
    </div>

    {{-- Kota --}}
    <div>
        <label for="filter-city" class="form-label">Kota</label>
        <select id="filter-city" wire:model.live="city"
                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Semua kota</option>
            @foreach ($cityOptions as $option)
                <option value="{{ $option }}">{{ $option }}</option>
            @endforeach
        </select>
    </div>

    {{-- Tanggal & tamu --}}
    <div>
        <span class="form-label">Tanggal Menginap</span>
        <div class="space-y-2">
            <input type="date" wire:model.live="checkIn" min="{{ now()->toDateString() }}"
                   aria-label="Check-in"
                   class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <input type="date" wire:model.live="checkOut" min="{{ now()->addDay()->toDateString() }}"
                   aria-label="Check-out"
                   class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @error('checkOut') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="filter-guests" class="form-label">Jumlah Tamu</label>
        <select id="filter-guests" wire:model.live="guests"
                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @foreach (range(1, 10) as $n)
                <option value="{{ $n }}">{{ $n }} orang</option>
            @endforeach
        </select>
    </div>

    {{-- Harga --}}
    <div>
        <span class="form-label">Rentang Harga per Malam</span>
        <div class="flex items-center gap-2">
            <input type="number" min="0" step="50000" wire:model.blur="minPrice" placeholder="Min"
                   aria-label="Harga minimum"
                   class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <span class="text-gray-400">–</span>
            <input type="number" min="0" step="50000" wire:model.blur="maxPrice" placeholder="Maks"
                   aria-label="Harga maksimum"
                   class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
    </div>

    {{-- Bintang --}}
    <fieldset>
        <legend class="form-label">Klasifikasi Bintang</legend>
        <div class="flex flex-wrap gap-1.5">
            @foreach (range(5, 1) as $star)
                <label class="cursor-pointer">
                    <input type="checkbox" wire:model.live="stars" value="{{ $star }}" class="peer sr-only">
                    <span class="inline-block rounded-lg border border-gray-300 px-2.5 py-1 text-xs font-medium text-gray-600
                                 peer-checked:border-indigo-600 peer-checked:bg-indigo-50 peer-checked:text-indigo-700">
                        {{ $star }} ★
                    </span>
                </label>
            @endforeach
        </div>
    </fieldset>

    {{-- Fasilitas --}}
    @if ($facilityOptions->isNotEmpty())
        <fieldset>
            <legend class="form-label">Fasilitas</legend>
            <div class="max-h-56 space-y-1.5 overflow-y-auto pr-1">
                @foreach ($facilityOptions as $facility)
                    <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" wire:model.live="facilities" value="{{ $facility->id }}"
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span>{{ $facility->name }}</span>
                    </label>
                @endforeach
            </div>
        </fieldset>
    @endif
</div>
