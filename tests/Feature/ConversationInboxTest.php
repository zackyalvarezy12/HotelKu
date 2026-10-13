<?php

namespace Tests\Feature;

use App\Enums\HotelStatus;
use App\Livewire\Chat\ConversationInbox;
use App\Models\Conversation;
use App\Models\Hotel;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewChatMessage;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ConversationInboxTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $guest;

    private Hotel $hotel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->owner = User::factory()->owner()->create();
        $this->guest = User::factory()->guest()->create();
        $this->hotel = Hotel::factory()->create([
            'owner_id' => $this->owner->id,
            'status' => HotelStatus::Active,
        ]);
    }

    private function conversation(?User $guest = null, ?Hotel $hotel = null): Conversation
    {
        return Conversation::factory()->create([
            'hotel_id' => ($hotel ?? $this->hotel)->id,
            'user_id' => ($guest ?? $this->guest)->id,
        ]);
    }

    public function test_guest_memulai_percakapan_dari_halaman_hotel(): void
    {
        $this->actingAs($this->guest)
            ->get(route('chat.inbox', ['hotel' => $this->hotel->slug]))
            ->assertSuccessful()
            ->assertSee('Kotak Pesan')
            ->assertSee($this->hotel->name);

        $this->assertDatabaseHas('conversations', [
            'hotel_id' => $this->hotel->id,
            'user_id' => $this->guest->id,
        ]);
    }

    public function test_pesan_guest_disimpan_dan_notifikasi_dikirim_ke_owner(): void
    {
        $conversation = $this->conversation();

        $this->actingAs($this->guest);
        Livewire::test(ConversationInbox::class)
            ->call('selectConversation', $conversation->id)
            ->set('body', 'Halo, apakah kamar tersedia?')
            ->call('send')
            ->assertHasNoErrors()
            ->assertSee('Halo, apakah kamar tersedia?');

        $message = Message::query()->where('conversation_id', $conversation->id)->sole();
        $this->assertSame($this->guest->id, $message->sender_id);
        $notification = $this->owner->notifications()->sole();
        $this->assertSame(NewChatMessage::class, $notification->type);
        $this->assertNull($notification->read_at);

        $this->actingAs($this->owner);
        $this->get(route('chat.inbox'))->assertSuccessful()->assertSee('1');
        Livewire::test(ConversationInbox::class)->call('selectConversation', $conversation->id);
        $this->assertNotNull($message->fresh()->read_at);
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_owner_dapat_mengirim_lampiran_gambar_dan_memberi_notifikasi_guest(): void
    {
        Notification::fake();
        Storage::fake('public');
        $conversation = $this->conversation();

        $this->actingAs($this->owner);
        Livewire::test(ConversationInbox::class)
            ->call('selectConversation', $conversation->id)
            ->set('attachment', UploadedFile::fake()->image('kamar.png'))
            ->call('send')
            ->assertHasNoErrors();

        $message = Message::query()->where('conversation_id', $conversation->id)->sole();
        $this->assertNotNull($message->attachment);
        Storage::disk('public')->assertExists($message->attachment);
        Notification::assertSentTo($this->guest, NewChatMessage::class);
    }

    public function test_guest_tidak_dapat_membuka_percakapan_tamu_lain(): void
    {
        $otherGuest = User::factory()->guest()->create();
        $conversation = $this->conversation($otherGuest);

        $this->actingAs($this->guest)
            ->get(route('chat.inbox', ['conversation' => $conversation->id]))
            ->assertNotFound();
    }
}