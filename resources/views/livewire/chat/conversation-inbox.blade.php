<div class="space-y-4">
    <header class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-lg font-bold text-gray-900 sm:text-xl">Kotak Pesan</h1>
        <a href="{{ route('bookings.index') }}" class="btn-secondary">Booking Saya</a>
    </header>

    <div class="grid min-h-[32rem] grid-cols-1 overflow-hidden rounded-lg border border-gray-200 bg-white md:grid-cols-[18rem_minmax(0,1fr)]">
        <aside class="border-b border-gray-200 md:border-b-0 md:border-r">
            <div class="border-b border-gray-100 px-4 py-3">
                <h2 class="text-sm font-semibold text-gray-800">Percakapan</h2>
            </div>
            <div class="max-h-[34rem] divide-y divide-gray-100 overflow-y-auto">
                @forelse ($conversations as $conversation)
                    @php
                        $otherParty = $isHotelUser ? $conversation->user : $conversation->hotel;
                        $preview = $conversation->latestMessage?->body;
                    @endphp
                    <button type="button" wire:click="selectConversation({{ $conversation->id }})"
                            class="block w-full px-4 py-3 text-left transition hover:bg-gray-50 {{ $selected?->id === $conversation->id ? 'bg-emerald-50' : '' }}">
                        <span class="flex items-center justify-between gap-2">
                            <span class="truncate text-sm font-semibold text-gray-900">{{ $otherParty->name }}</span>
                            @if ($conversation->unread_count > 0)
                                <span class="inline-flex min-w-5 items-center justify-center rounded-full bg-rose-600 px-1.5 py-0.5 text-[11px] font-semibold text-white">
                                    {{ $conversation->unread_count }}
                                </span>
                            @endif
                        </span>
                        <span class="mt-0.5 block truncate text-xs text-gray-500">
                            {{ $isHotelUser ? $conversation->hotel->name : $otherParty->city }}
                        </span>
                        <span class="mt-1 block truncate text-xs text-gray-400">
                            {{ $preview ?: ($conversation->booking_id ? 'Terkait booking' : 'Belum ada pesan') }}
                        </span>
                    </button>
                @empty
                    <p class="px-4 py-6 text-sm text-gray-500">Belum ada percakapan.</p>
                @endforelse
            </div>
            @if ($conversations->hasMorePages())
                <div class="border-t border-gray-100 p-3">{{ $conversations->links() }}</div>
            @endif
        </aside>

        <section wire:poll.5s class="flex min-h-[32rem] min-w-0 flex-col">
            @if ($selected)
                <header class="border-b border-gray-200 px-4 py-3">
                    <h2 class="truncate text-sm font-semibold text-gray-900">
                        {{ $isHotelUser ? $selected->user->name : $selected->hotel->name }}
                    </h2>
                    @if ($selected->booking_id)
                        <p class="mt-0.5 text-xs text-gray-500">Booking terkait #{{ $selected->booking_id }}</p>
                    @endif
                </header>

                <div class="flex-1 space-y-3 overflow-y-auto bg-gray-50 px-4 py-4" role="log" aria-live="polite">
                    @forelse ($selected->messages as $message)
                        <div class="flex {{ $message->sender_id === auth()->id() ? 'justify-end' : 'justify-start' }}">
                            <article class="max-w-[85%] rounded-lg px-3 py-2 shadow-sm sm:max-w-[72%] {{ $message->sender_id === auth()->id() ? 'bg-emerald-700 text-white' : 'border border-gray-200 bg-white text-gray-800' }}">
                                @if ($message->attachment_url)
                                    <a href="{{ $message->attachment_url }}" target="_blank" rel="noopener" class="mb-2 block">
                                        <img src="{{ $message->attachment_url }}" alt="Lampiran dari {{ $message->sender->name }}"
                                             class="max-h-64 rounded-md object-contain" loading="lazy">
                                    </a>
                                @endif
                                @if (filled($message->body))
                                    <p class="whitespace-pre-wrap break-words text-sm">{{ $message->body }}</p>
                                @endif
                                <p class="mt-1 text-right text-[10px] {{ $message->sender_id === auth()->id() ? 'text-emerald-100' : 'text-gray-400' }}">
                                    {{ $message->created_at->translatedFormat('d M, H:i') }}
                                </p>
                            </article>
                        </div>
                    @empty
                        <p class="py-10 text-center text-sm text-gray-500">Mulai percakapan dengan mengirim pesan.</p>
                    @endforelse
                </div>

                <form wire:submit="send" class="space-y-2 border-t border-gray-200 bg-white p-3 sm:p-4">
                    <label for="chat-message" class="sr-only">Pesan</label>
                    <textarea id="chat-message" wire:model="body" rows="2" maxlength="2000"
                              placeholder="Tulis pesan..."
                              class="w-full resize-y rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-600 focus:ring-emerald-600"></textarea>
                    @error('body') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <label for="chat-attachment" class="inline-flex cursor-pointer items-center gap-1.5 text-sm font-medium text-gray-600 hover:text-emerald-700">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m7 13 5-5a3 3 0 0 1 4 4l-6 6a5 5 0 0 1-7-7l6-6" />
                                </svg>
                                Lampirkan gambar
                            </label>
                            <input id="chat-attachment" type="file" wire:model="attachment" accept="image/jpeg,image/png,image/webp" class="sr-only">
                            @if ($attachment)
                                <span class="ml-2 text-xs text-gray-500">{{ $attachment->getClientOriginalName() }}</span>
                            @endif
                            @error('attachment') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            <span wire:loading wire:target="attachment" class="ml-2 text-xs text-gray-500">Memuat gambar...</span>
                        </div>
                        <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="send,attachment">
                            <span wire:loading.remove wire:target="send">Kirim</span>
                            <span wire:loading wire:target="send">Mengirim...</span>
                        </button>
                    </div>
                </form>
            @else
                <div class="flex flex-1 items-center justify-center px-6 py-12 text-center">
                    <p class="text-sm text-gray-500">Pilih percakapan untuk melihat pesan.</p>
                </div>
            @endif
        </section>
    </div>
</div>