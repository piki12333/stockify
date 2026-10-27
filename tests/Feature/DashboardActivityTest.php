<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('categories', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
        });
        Schema::create('suppliers', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
        });
        Schema::create('products', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('category_id');
            $table->unsignedInteger('supplier_id');
            $table->string('product_code')->nullable();
            $table->string('name');
            $table->decimal('purchase_price', 15, 2)->default(0);
            $table->integer('stock')->default(0);
            $table->integer('min_stock')->default(0);
            $table->timestamps();
        });
    }

    public function test_admin_dashboard_shows_latest_user_activities(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create([
            'name' => 'Staff Contoh',
            'email' => 'staff.dashboard@example.test',
            'role' => User::ROLE_STAFF,
        ]);
        $this->createDashboardInventoryData();

        DB::table('account_activities')->insert([
            'event' => 'login',
            'user_id' => $staff->id,
            'description' => 'Masuk ke akun.',
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ]);
        DB::table('account_activities')->insert([
            'event' => 'profile-updated',
            'user_id' => $staff->id,
            'description' => 'Mengubah informasi profil.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Aktivitas pengguna terbaru')
            ->assertSee('Staff Contoh')
            ->assertSee('Staff Gudang')
            ->assertSee('Mengubah informasi profil.')
            ->assertSee('Masuk ke akun.')
            ->assertSee('Lihat laporan aktivitas')
            ->assertSeeInOrder(['Mengubah informasi profil.', 'Masuk ke akun.']);
    }

    public function test_dashboard_hides_user_activities_from_non_admin_roles(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $this->createDashboardInventoryData();
        $manager->activities()->create([
            'event' => 'password-updated',
            'description' => 'Aktivitas rahasia pengujian.',
        ]);

        $this->actingAs($manager)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Aktivitas pengguna terbaru')
            ->assertDontSee('Aktivitas rahasia pengujian.');
    }

    private function createDashboardInventoryData(): void
    {
        $categoryId = DB::table('categories')->insertGetId(['name' => 'Kategori Uji']);
        $supplierId = DB::table('suppliers')->insertGetId(['name' => 'Supplier Uji']);
        $productId = DB::table('products')->insertGetId([
            'category_id' => $categoryId,
            'supplier_id' => $supplierId,
            'product_code' => 'UJI-01',
            'name' => 'Produk Uji',
            'purchase_price' => 1000,
            'stock' => 10,
            'min_stock' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('transactions')->insert([
            'product_id' => $productId,
            'type' => 'in',
            'quantity' => 2,
            'notes' => 'Transaksi dashboard',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
