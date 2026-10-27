<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApplicationSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_application_name_and_logo(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee('Pengaturan Umum')
            ->assertSee('Logo bawaan Stockify');

        $this->put(route('settings.update'), [
            'app_name' => 'Gudang Prima',
            'logo' => UploadedFile::fake()->createWithContent(
                'gudang-prima.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/n7sAAAAASUVORK5CYII=')
            ),
        ])
            ->assertRedirect(route('settings.index'))
            ->assertSessionHas('success');

        $logoPath = DB::table('app_settings')->value('logo_path');
        $this->assertDatabaseHas('app_settings', ['app_name' => 'Gudang Prima']);
        $this->assertStringStartsWith('branding/', $logoPath);
        Storage::disk('public')->assertExists($logoPath);

        $this->get(route('settings.index'))
            ->assertOk()
            ->assertSee('Gudang Prima')
            ->assertSee('Dashboard - Gudang Prima');

        $this->post(route('logout'));
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Masuk - Gudang Prima')
            ->assertSee('Logo Gudang Prima');
    }

    public function test_admin_can_change_application_name_without_replacing_existing_logo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('branding/current.png', 'logo');
        $admin = User::factory()->admin()->create();

        DB::table('app_settings')->insert([
            'app_name' => 'Stockify Lama',
            'logo_path' => 'branding/current.png',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->put(route('settings.update'), ['app_name' => 'Stockify Baru'])
            ->assertRedirect(route('settings.index'));

        $this->assertDatabaseHas('app_settings', [
            'app_name' => 'Stockify Baru',
            'logo_path' => 'branding/current.png',
        ]);
        Storage::disk('public')->assertExists('branding/current.png');
    }

    public function test_only_admin_can_manage_application_settings(): void
    {
        foreach ([User::ROLE_MANAGER, User::ROLE_STAFF] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->get(route('settings.index'))
                ->assertForbidden();

            $this->put(route('settings.update'), ['app_name' => 'Nama Tidak Diizinkan'])
                ->assertForbidden();
        }
    }

    public function test_application_settings_reject_invalid_logo_and_empty_name(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('settings.index'))
            ->put(route('settings.update'), [
                'app_name' => '',
                'logo' => UploadedFile::fake()->create('document.pdf', 20, 'application/pdf'),
            ])
            ->assertRedirect(route('settings.index'))
            ->assertSessionHasErrors(['app_name', 'logo']);

        $this->assertDatabaseCount('app_settings', 0);
    }
}
