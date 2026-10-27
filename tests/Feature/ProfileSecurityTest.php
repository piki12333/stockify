<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileSecurityTest extends TestCase
{
    use RefreshDatabase;

    // Pastikan login membuat sesi dan menampilkan halaman profil yang dilindungi.
    public function test_user_can_log_in_and_view_the_protected_profile(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Keamanan')
            ->assertSee('Aktivitas akun');
        $this->assertDatabaseHas('sessions', ['user_id' => $user->id]);
        $this->assertDatabaseHas('account_activities', [
            'user_id' => $user->id,
            'event' => 'login',
        ]);
    }

    // Pastikan kata sandi lama diverifikasi dan sesi perangkat lain dicabut.
    public function test_password_change_requires_the_current_password_and_revokes_other_sessions(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('profile.edit'));

        DB::table('sessions')->insert([
            'id' => 'another-device',
            'user_id' => $user->id,
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);

        $this->patch(route('profile.password.update'), [
            'current_password' => 'incorrect',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertSessionHasErrors('current_password');

        $this->patch(route('profile.password.update'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect(route('profile.edit'));

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertDatabaseMissing('sessions', ['id' => 'another-device']);
        $this->assertDatabaseHas('account_activities', [
            'user_id' => $user->id,
            'event' => 'password-updated',
        ]);
    }

    // Pastikan pengguna hanya dapat mengakhiri sesi milik akunnya sendiri.
    public function test_user_can_end_only_their_own_other_sessions(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->actingAs($user)->get(route('profile.edit'));

        DB::table('sessions')->insert([
            [
                'id' => 'session-to-end',
                'user_id' => $user->id,
                'payload' => '',
                'last_activity' => now()->timestamp,
            ],
            [
                'id' => 'unrelated-user-session',
                'user_id' => $otherUser->id,
                'payload' => '',
                'last_activity' => now()->timestamp,
            ],
        ]);

        $this->delete(route('profile.sessions.destroy', 'session-to-end'))
            ->assertRedirect(route('profile.edit'));

        $this->assertDatabaseMissing('sessions', ['id' => 'session-to-end']);
        $this->assertDatabaseHas('sessions', ['id' => 'unrelated-user-session']);
        $this->assertDatabaseHas('account_activities', [
            'user_id' => $user->id,
            'event' => 'session-revoked',
        ]);
    }
}
