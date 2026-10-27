<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RoleAccessTest extends TestCase
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
            $table->text('description')->nullable();
            $table->decimal('purchase_price', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->integer('stock')->default(0);
            $table->integer('min_stock')->default(0);
            $table->string('image_url')->nullable();
            $table->timestamps();
        });

        if (! Schema::hasColumn('transactions', 'current_stock')) {
            Schema::table('transactions', function (Blueprint $table): void {
                $table->integer('current_stock')->default(0);
            });
        }
    }

    public function test_staff_cannot_open_administration_pages_or_change_master_data(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $this->actingAs($staff);

        $this->get('/users')->assertForbidden();
        $this->get('/categories')->assertForbidden();
        $this->get('/suppliers')->assertForbidden();
        $this->get('/reports')->assertForbidden();
        $this->delete('/produk/1')->assertForbidden();
        $this->delete('/stock-opnames/1')->assertForbidden();
    }

    public function test_manager_can_view_activity_report_but_cannot_manage_users_or_categories(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $this->actingAs($manager);

        $this->get('/users')->assertForbidden();
        $this->get('/categories')->assertForbidden();
        $this->post('/categories', ['name' => 'Tidak Diizinkan'])->assertForbidden();
        $this->get('/reports?report=stock')->assertOk();
        $this->get('/reports?report=transactions')->assertOk();
        $this->get('/reports?report=activities')
            ->assertOk()
            ->assertSee('Aktivitas Pengguna');
        $this->get('/reports/export?report=activities')->assertOk();

        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $this->actingAs($staff)
            ->get('/reports?report=activities')
            ->assertForbidden();
        $this->get('/reports/export?report=activities')->assertForbidden();
    }

    public function test_admin_can_create_and_change_user_roles(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Manajemen Pengguna')
            ->assertSee('Admin');

        $this->post(route('users.store'), [
            'name' => 'Staff Gudang',
            'email' => 'staff@example.test',
            'password' => 'password-aman',
            'role' => User::ROLE_STAFF,
        ])->assertRedirect(route('users.index'));

        $staff = User::where('email', 'staff@example.test')->firstOrFail();
        $this->assertSame(User::ROLE_STAFF, $staff->role);

        $this->put(route('users.update', $staff), [
            'name' => 'Manajer Gudang',
            'email' => 'manager@example.test',
            'password' => '',
            'role' => User::ROLE_MANAGER,
        ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'name' => 'Manajer Gudang',
            'email' => 'manager@example.test',
            'role' => User::ROLE_MANAGER,
        ]);
    }

    public function test_admin_cannot_demote_or_delete_the_account_in_use(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => User::ROLE_MANAGER,
            ])
            ->assertSessionHasErrors('role');

        $this->delete(route('users.destroy', $admin))->assertStatus(422);
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => User::ROLE_ADMIN]);
    }

    public function test_stock_transactions_record_the_user_in_history_and_reports(): void
    {
        $manager = User::factory()->create([
            'name' => 'Manajer Uji',
            'role' => User::ROLE_MANAGER,
        ]);
        $staff = User::factory()->create([
            'name' => 'Staff Uji',
            'role' => User::ROLE_STAFF,
        ]);
        $categoryId = DB::table('categories')->insertGetId(['name' => 'Peralatan']);
        $productId = DB::table('products')->insertGetId([
            'product_code' => 'PR-01',
            'name' => 'Produk Uji',
            'category_id' => $categoryId,
            'supplier_id' => 1,
            'stock' => 10,
            'min_stock' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($manager)
            ->post(route('transactions.store'), [
                'product_id' => $productId,
                'type' => 'in',
                'quantity' => 4,
                'notes' => 'Penerimaan uji',
                'assigned_to' => $staff->id,
            ])
            ->assertRedirect(route('transactions.index'));

        $this->post(route('transactions.store'), [
            'product_id' => $productId,
            'type' => 'out',
            'quantity' => 3,
            'notes' => 'Pengeluaran uji',
            'assigned_to' => $staff->id,
        ])->assertRedirect(route('transactions.index'));

        $this->assertDatabaseHas('products', ['id' => $productId, 'stock' => 10]);
        $this->assertDatabaseHas('transactions', [
            'product_id' => $productId,
            'user_id' => $manager->id,
            'assigned_to' => $staff->id,
            'type' => 'in',
            'quantity' => 4,
            'status' => 'pending',
        ]);

        $taskIds = DB::table('transactions')->orderBy('id')->pluck('id');
        $this->actingAs($staff)
            ->post(route('transactions.confirm', $taskIds[0]))
            ->assertRedirect(route('transactions.index'));
        $this->post(route('transactions.confirm', $taskIds[1]))
            ->assertRedirect(route('transactions.index'));

        $this->assertDatabaseHas('transactions', [
            'product_id' => $productId,
            'user_id' => $manager->id,
            'confirmed_by' => $staff->id,
            'type' => 'in',
            'quantity' => 4,
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('transactions', [
            'product_id' => $productId,
            'user_id' => $manager->id,
            'confirmed_by' => $staff->id,
            'type' => 'out',
            'quantity' => 3,
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('products', ['id' => $productId, 'stock' => 11]);
        $this->assertDatabaseHas('account_activities', [
            'user_id' => $staff->id,
            'event' => 'transaction-in',
            'description' => 'Mengonfirmasi barang masuk: Produk Uji sebanyak 4 unit.',
        ]);
        $this->assertDatabaseHas('account_activities', [
            'user_id' => $staff->id,
            'event' => 'transaction-out',
            'description' => 'Mengonfirmasi barang keluar: Produk Uji sebanyak 3 unit.',
        ]);

        $this->get(route('transactions.index'))
            ->assertOk()
            ->assertSee('Dicatat oleh')
            ->assertSee('Dikonfirmasi oleh')
            ->assertSee('Manajer Uji')
            ->assertSee('Staff Uji');

        $this->actingAs($manager);
        $this->get(route('reports.index', ['report' => 'transactions']))
            ->assertOk()
            ->assertSee('Dibuat oleh')
            ->assertSee('Manajer Uji')
            ->assertSee('Dikonfirmasi oleh')
            ->assertSee('Staff Uji');

        $export = $this->get(route('reports.export', ['report' => 'transactions']));
        $export->assertOk();
        $this->assertStringContainsString('Dibuat oleh', $export->streamedContent());
        $this->assertStringContainsString('Manajer Uji', $export->streamedContent());
        $this->assertStringContainsString('Staff Uji', $export->streamedContent());

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)
            ->get(route('reports.index', ['report' => 'activities']))
            ->assertOk()
            ->assertSee('Staff Uji')
            ->assertSee('Mengonfirmasi barang masuk: Produk Uji sebanyak 4 unit.')
            ->assertSee('Mengonfirmasi barang keluar: Produk Uji sebanyak 3 unit.');
    }
}
