<div class="mx-auto max-w-2xl space-y-4">
    <nav class="flex items-center gap-2 text-sm text-gray-500" aria-label="Breadcrumb">
        <a href="{{ route('bookings.index') }}" class="hover:text-emerald-700">Booking Saya</a>
        <span>/</span>
        <span class="font-medium text-gray-700">Tulis Ulasan</span>
    </nav>

    <section class="rounded-lg border border-gray-200 bg-white p-4 sm:p-6">
        <h1 class="text-lg font-bold text-gray-900">Ulasan untuk {{ $booking->hotel->name }}</h1>
        <p class="mt-1 text-sm text-gray-500">
            Booking {{ $booking->code }} · {{ $booking->check_in->translatedFormat('d M Y') }} – {{ $booking->check_out->translatedFormat('d M Y') }}
        </p>

        <form wire:submit="submit" class="mt-5 space-y-5">
            <fieldset>
                <legend class="text-sm font-medium text-gray-700">Nilai pengalaman Anda</legend>
                <div class="mt-2 flex flex-wrap gap-2" role="radiogroup" aria-label="Rating">
                    @foreach (range(1, 5) as $value)
                        <label class="cursor-pointer">
                            <input type="radio" wire:model="rating" value="{{ $value }}" class="peer sr-only">
                            <span class="inline-flex h-11 min-w-11 items-center justify-center rounded-md border px-3 text-sm font-semibold transition peer-checked:border-amber-500 peer-checked:bg-amber-50 peer-checked:text-amber-700 peer-focus-visible:ring-2 peer-focus-visible:ring-amber-500">
                                {{ $value }} <span class="ml-1" aria-hidden="true">★</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('rating') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </fieldset>

            <div>
                <label for="review-comment" class="form-label">Cerita singkat (opsional)</label>
                <textarea id="review-comment" wire:model="comment" rows="5" maxlength="2000"
                          class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-600 focus:ring-emerald-600"></textarea>
                @error('comment') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex flex-col-reverse justify-end gap-2 sm:flex-row">
                <a href="{{ route('bookings.index') }}" class="btn-secondary justify-center">Kembali</a>
                <button type="submit" class="btn-primary justify-center" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="submit">Kirim Ulasan</span>
                    <span wire:loading wire:target="submit">Mengirim...</span>
                </button>
            </div>
        </form>
    </section>
</div>