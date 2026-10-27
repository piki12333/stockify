<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StockOpnameApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('products', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
            $table->integer('stock')->default(0);
            $table->timestamps();
        });

        Schema::create('stock_opnames', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('user_id');
            $table->integer('system_stock');
            $table->integer('physical_stock');
            $table->integer('difference');
            $table->string('notes')->nullable();
            $table->string('status', 20)->default('approved');
            $table->unsignedInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_notes')->nullable();
            $table->timestamps();
            $table->index('status');
        });
    }

    public function test_staff_submission_waits_for_approval_without_changing_stock(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $productId = $this->createProduct(10);

        $this->actingAs($staff)
            ->post(route('stock-opnames.store'), [
                'product_id' => $productId,
                'physical_stock' => 8,
                'notes' => 'Selisih hitung',
            ])
            ->assertRedirect(route('stock-opnames.index'));

        $this->assertDatabaseHas('products', ['id' => $productId, 'stock' => 10]);
        $this->assertDatabaseHas('stock_opnames', [
            'product_id' => $productId,
            'user_id' => $staff->id,
            'system_stock' => 10,
            'physical_stock' => 8,
            'difference' => -2,
            'status' => 'pending',
        ]);
    }

    public function test_manager_approval_updates_stock_and_recalculates_difference(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $productId = $this->createProduct(10);
        $opnameId = $this->createPendingOpname($productId, $staff->id, 8);

        DB::table('products')->where('id', $productId)->update(['stock' => 12]);

        $this->actingAs($manager)
            ->post(route('stock-opnames.approve', $opnameId), ['review_notes' => 'Sudah diperiksa'])
            ->assertRedirect(route('stock-opnames.index'));

        $this->assertDatabaseHas('products', ['id' => $productId, 'stock' => 8]);
        $this->assertDatabaseHas('stock_opnames', [
            'id' => $opnameId,
            'system_stock' => 12,
            'difference' => -4,
            'status' => 'approved',
            'reviewed_by' => $manager->id,
            'review_notes' => 'Sudah diperiksa',
        ]);
    }

    public function test_manager_can_reject_with_reason_without_changing_stock(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $productId = $this->createProduct(10);
        $opnameId = $this->createPendingOpname($productId, $staff->id, 8);

        $this->actingAs($manager)
            ->post(route('stock-opnames.reject', $opnameId), ['review_notes' => 'Mohon hitung ulang'])
            ->assertRedirect(route('stock-opnames.index'));

        $this->assertDatabaseHas('products', ['id' => $productId, 'stock' => 10]);
        $this->assertDatabaseHas('stock_opnames', [
            'id' => $opnameId,
            'status' => 'rejected',
            'reviewed_by' => $manager->id,
            'review_notes' => 'Mohon hitung ulang',
        ]);
    }

    public function test_manager_sees_review_controls_for_another_users_pending_submission(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $productId = $this->createProduct(10);
        $opnameId = $this->createPendingOpname($productId, $staff->id, 8);

        $this->actingAs($manager)
            ->get(route('stock-opnames.index'))
            ->assertOk()
            ->assertSee('Menunggu Persetujuan')
            ->assertSee(route('stock-opnames.approve', $opnameId))
            ->assertSee(route('stock-opnames.reject', $opnameId));
    }

    public function test_staff_cannot_review_and_users_cannot_review_their_own_submission(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $productId = $this->createProduct(10);
        $staffOpnameId = $this->createPendingOpname($productId, $manager->id, 8);

        $this->actingAs($staff)
            ->post(route('stock-opnames.approve', $staffOpnameId))
            ->assertForbidden();

        $managerOpnameId = $this->createPendingOpname($productId, $manager->id, 7);
        $this->actingAs($manager)
            ->post(route('stock-opnames.approve', $managerOpnameId))
            ->assertForbidden();

        $this->assertDatabaseHas('products', ['id' => $productId, 'stock' => 10]);
        $this->assertDatabaseHas('stock_opnames', ['id' => $managerOpnameId, 'status' => 'pending']);
    }

    private function createProduct(int $stock): int
    {
        return DB::table('products')->insertGetId([
            'name' => 'Produk Uji',
            'stock' => $stock,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createPendingOpname(int $productId, int $userId, int $physicalStock): int
    {
        return DB::table('stock_opnames')->insertGetId([
            'product_id' => $productId,
            'user_id' => $userId,
            'system_stock' => 10,
            'physical_stock' => $physicalStock,
            'difference' => $physicalStock - 10,
            'notes' => null,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
