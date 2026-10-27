<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use App\Notifications\TransactionRecorded;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_notification_is_shown_and_can_be_marked_as_read(): void
    {
        $user = User::factory()->create();
        $user->notify(new TransactionRecorded(new Transaction([
            'type' => 'in',
            'quantity' => 7,
        ])));

        $notification = $user->notifications()->firstOrFail();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Barang masuk dikonfirmasi')
            ->assertSee('7 unit produk ditambahkan ke stok.')
            ->assertSee('1 belum dibaca');

        $this->post(route('notifications.read', $notification->id))
            ->assertRedirect(route('transactions.index'));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $owner->notify(new TransactionRecorded(new Transaction([
            'type' => 'out',
            'quantity' => 2,
        ])));
        $notification = $owner->notifications()->firstOrFail();

        $this->actingAs($otherUser)
            ->post(route('notifications.read', $notification->id))
            ->assertNotFound();

        $this->assertNull($notification->fresh()->read_at);
    }
}
