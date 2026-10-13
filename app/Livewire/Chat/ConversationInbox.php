<?php

namespace App\Livewire\Chat;

use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Hotel;
use App\Models\Message;
use App\Notifications\NewChatMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.public')]
class ConversationInbox extends Component
{
    use WithFileUploads;

    public ?int $selectedConversationId = null;

    public string $body = '';

    public $attachment = null;

    public function mount(): void
    {
        $hotelSlug = request()->query('hotel');
        $bookingId = request()->query('booking');
        $conversationId = request()->query('conversation');

        if (filled($hotelSlug)) {
            $hotel = Hotel::withoutGlobalScopes()
                ->publiclyVisible()
                ->where('slug', $hotelSlug)
                ->firstOrFail();

            $managedHotelIds = auth()->user()->managedHotelIds();
            abort_if($managedHotelIds !== null && in_array($hotel->id, $managedHotelIds, true), 403);

            $this->selectedConversationId = $this->getOrCreateConversation($hotel, auth()->user()->getKey())->getKey();
        } elseif (filled($bookingId)) {
            $booking = Booking::withoutGlobalScopes()->with('hotel')->findOrFail($bookingId);
            $managedHotelIds = auth()->user()->managedHotelIds();

            if ($managedHotelIds === null) {
                abort_unless((int) $booking->user_id === (int) auth()->id(), 403);
            } else {
                abort_unless(in_array($booking->hotel_id, $managedHotelIds, true), 403);
            }

            $conversation = DB::transaction(function () use ($booking) {
                Hotel::withoutGlobalScopes()->whereKey($booking->hotel_id)->lockForUpdate()->firstOrFail();

                return Conversation::withoutGlobalScopes()->firstOrCreate(
                    ['hotel_id' => $booking->hotel_id, 'user_id' => $booking->user_id],
                    ['booking_id' => $booking->getKey()],
                );
            });

            if (! $conversation->booking_id) {
                $conversation->update(['booking_id' => $booking->getKey()]);
            }

            $this->selectedConversationId = $conversation->getKey();
        } elseif (filled($conversationId)) {
            $this->selectConversation((int) $conversationId);
        }
    }

    public function selectConversation(int $conversationId): void
    {
        $conversation = $this->conversationQuery()->findOrFail($conversationId);
        $conversation->markAsReadFor(auth()->user());
        $this->markNotificationsRead($conversationId);
        $this->selectedConversationId = $conversation->getKey();
    }

    public function send(): void
    {
        $validated = $this->validate([
            'body' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'body.max' => 'Pesan maksimal 2.000 karakter.',
            'attachment.image' => 'Lampiran harus berupa gambar.',
            'attachment.mimes' => 'Format gambar yang didukung: JPG, PNG, atau WebP.',
            'attachment.max' => 'Ukuran gambar maksimal 2 MB.',
        ]);

        if (blank($validated['body']) && ! $this->attachment) {
            throw ValidationException::withMessages(['body' => 'Tulis pesan atau pilih gambar lampiran.']);
        }

        if (! $this->selectedConversationId) {
            throw ValidationException::withMessages(['body' => 'Pilih percakapan terlebih dahulu.']);
        }

        $conversation = $this->conversationQuery()->with(['hotel.owner', 'user'])->findOrFail($this->selectedConversationId);
        $path = $this->attachment?->store('chat', 'public');

        $message = $conversation->messages()->create([
            'sender_id' => auth()->id(),
            'body' => trim((string) $validated['body']),
            'attachment' => $path,
        ]);

        $conversation->update(['last_message_at' => now()]);

        $recipient = auth()->user()->managedHotelIds() === null
            ? $conversation->hotel->owner
            : $conversation->user;

        if ($recipient && $recipient->isNot(auth()->user())) {
            Notification::send($recipient, new NewChatMessage($message));
        }

        $this->reset('body', 'attachment');
        $this->resetValidation();
    }

    private function getOrCreateConversation(Hotel $hotel, int $guestId): Conversation
    {
        return DB::transaction(function () use ($hotel, $guestId) {
            Hotel::withoutGlobalScopes()->whereKey($hotel->getKey())->lockForUpdate()->firstOrFail();

            return Conversation::withoutGlobalScopes()->firstOrCreate([
                'hotel_id' => $hotel->getKey(),
                'user_id' => $guestId,
            ]);
        });
    }

    private function markNotificationsRead(int $conversationId): void
    {
        auth()->user()->unreadNotifications()
            ->where('data->conversation_id', $conversationId)
            ->update(['read_at' => now()]);
    }

    private function conversationQuery(): Builder
    {
        $user = auth()->user();
        $managedHotelIds = $user->managedHotelIds();

        return Conversation::withoutGlobalScopes()
            ->when(
                $managedHotelIds === null,
                fn (Builder $query) => $query->where('user_id', $user->getKey()),
                fn (Builder $query) => $query->whereIn('hotel_id', $managedHotelIds),
            );
    }

    public function render(): View
    {
        $selected = $this->selectedConversationId
            ? $this->conversationQuery()->with(['hotel', 'user'])->findOrFail($this->selectedConversationId)
            : null;

        if ($selected) {
            $selected->markAsReadFor(auth()->user());
            $this->markNotificationsRead($selected->getKey());
            $messages = $selected->messages()->with('sender')->latest('id')->limit(100)->get()->reverse()->values();
            $selected->setRelation('messages', $messages);
        }

        $conversations = $this->conversationQuery()
            ->with(['hotel', 'user', 'latestMessage.sender'])
            ->withCount(['messages as unread_count' => fn (Builder $query) => $query
                ->whereNull('read_at')
                ->where('sender_id', '!=', auth()->id())])
            ->orderByDesc('last_message_at')
            ->paginate(20);

        return view('livewire.chat.conversation-inbox', [
            'conversations' => $conversations,
            'selected' => $selected,
            'isHotelUser' => auth()->user()->managedHotelIds() !== null,
        ]);
    }
}