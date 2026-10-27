<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\TransactionRecorded;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TransactionTaskWorkflowTest extends TestCase
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

    public function test_manager_creates_task_and_assigned_staff_confirmation_updates_stock(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $productId = $this->createProduct(10);

        $this->actingAs($manager)
            ->get(route('transactions.index'))
            ->assertOk()
            ->assertSee('Produk Tugas Uji')
            ->assertSee('Stok: 10');

        $this->actingAs($manager)
            ->post(route('transactions.store'), [
                'product_id' => $productId,
                'type' => 'in',
                'quantity' => 5,
                'assigned_to' => $staff->id,
                'notes' => 'Penerimaan pesanan',
            ])
            ->assertRedirect(route('transactions.index'));

        $task = DB::table('transactions')->first();
        $this->assertSame(10, (int) DB::table('products')->where('id', $productId)->value('stock'));
        $this->assertSame('pending', $task->status);
        $this->assertSame($staff->id, (int) $task->assigned_to);
        $this->assertDatabaseCount('notifications', 0);

        $this->actingAs($manager)
            ->get(route('reports.index', ['report' => 'transactions']))
            ->assertOk()
            ->assertDontSee('Produk Tugas Uji')
            ->assertSee('Tidak ada transaksi pada periode dan filter ini.');

        $this->actingAs($staff)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Tugas gudang Anda')
            ->assertSee('Periksa barang masuk')
            ->assertSee('Produk Tugas Uji')
            ->assertSee('Konfirmasi tugas')
            ->assertSee('Konfirmasi transaksi')
            ->assertSee('Ya, konfirmasi')
            ->assertDontSee('onsubmit="return confirm(');

        $this->get(route('transactions.index'))
            ->assertOk()
            ->assertSee('Ya, konfirmasi')
            ->assertDontSee('onsubmit="return confirm(');

        $this->post(route('transactions.confirm', $task->id))
            ->assertRedirect(route('transactions.index'));

        $this->assertSame(15, (int) DB::table('products')->where('id', $productId)->value('stock'));
        $this->assertDatabaseHas('transactions', [
            'id' => $task->id,
            'status' => 'completed',
            'confirmed_by' => $staff->id,
            'current_stock' => 15,
        ]);
        $this->assertDatabaseHas('account_activities', [
            'user_id' => $staff->id,
            'event' => 'transaction-in',
            'description' => 'Mengonfirmasi barang masuk: Produk Tugas Uji sebanyak 5 unit.',
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $manager->id,
            'type' => TransactionRecorded::class,
        ]);
    }

    public function test_only_admin_and_manager_can_create_tasks_and_only_staff_can_confirm(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $productId = $this->createProduct(10);

        $this->actingAs($staff)
            ->post(route('transactions.store'), [
                'product_id' => $productId,
                'type' => 'out',
                'quantity' => 1,
                'assigned_to' => $staff->id,
            ])
            ->assertForbidden();

        $this->actingAs($manager)
            ->post(route('transactions.store'), [
                'product_id' => $productId,
                'type' => 'out',
                'quantity' => 1,
                'assigned_to' => $manager->id,
            ])
            ->assertSessionHasErrors('assigned_to');
    }

    public function test_staff_cannot_confirm_a_task_assigned_to_another_staff_member(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $assignedStaff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $otherStaff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $productId = $this->createProduct(10);
        $taskId = $this->createTask($manager->id, $assignedStaff->id, $productId, 'out', 3);

        $this->actingAs($otherStaff)
            ->post(route('transactions.confirm', $taskId))
            ->assertForbidden();

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Produk Tugas Uji')
            ->assertSee('Tidak ada tugas transaksi yang menunggu konfirmasi.');

        $this->assertSame(10, (int) DB::table('products')->where('id', $productId)->value('stock'));
        $this->assertDatabaseHas('transactions', ['id' => $taskId, 'status' => 'pending']);
    }

    public function test_outgoing_task_cannot_be_confirmed_when_stock_is_no_longer_sufficient(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $productId = $this->createProduct(10);
        $taskId = $this->createTask($manager->id, $staff->id, $productId, 'out', 8);
        DB::table('products')->where('id', $productId)->update(['stock' => 5]);

        $this->actingAs($staff)
            ->from(route('transactions.index'))
            ->post(route('transactions.confirm', $taskId))
            ->assertRedirect(route('transactions.index'))
            ->assertSessionHas('error');

        $this->assertSame(5, (int) DB::table('products')->where('id', $productId)->value('stock'));
        $this->assertDatabaseHas('transactions', ['id' => $taskId, 'status' => 'pending']);
    }

    private function createProduct(int $stock): int
    {
        $categoryId = DB::table('categories')->insertGetId(['name' => 'Kategori Uji']);
        $supplierId = DB::table('suppliers')->insertGetId(['name' => 'Supplier Uji']);

        return DB::table('products')->insertGetId([
            'category_id' => $categoryId,
            'supplier_id' => $supplierId,
            'product_code' => 'TUGAS-01',
            'name' => 'Produk Tugas Uji',
            'purchase_price' => 1000,
            'stock' => $stock,
            'min_stock' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createTask(int $creatorId, int $staffId, int $productId, string $type, int $quantity): int
    {
        return DB::table('transactions')->insertGetId([
            'product_id' => $productId,
            'user_id' => $creatorId,
            'assigned_to' => $staffId,
            'type' => $type,
            'quantity' => $quantity,
            'status' => 'pending',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
