<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductDetailTest extends TestCase
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
            $table->string('product_code')->nullable();
            $table->string('name');
            $table->unsignedInteger('category_id');
            $table->unsignedInteger('supplier_id');
            $table->text('description')->nullable();
            $table->decimal('purchase_price', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->integer('stock')->default(0);
            $table->integer('min_stock')->default(0);
            $table->string('image_url')->nullable();
            $table->timestamps();
        });
    }

    public function test_staff_can_view_product_stock_details_and_transaction_history_without_prices(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $productId = $this->createProduct();
        $this->createTransaction($productId, $manager->id, $staff->id);

        $this->actingAs($staff)
            ->get(route('produk.show', $productId))
            ->assertOk()
            ->assertSee('Detail Produk')
            ->assertSee('Mouse Wireless')
            ->assertSee('EL-100')
            ->assertSee('Elektronik')
            ->assertSee('Supplier Utama')
            ->assertSee('32 unit')
            ->assertSee('5 unit')
            ->assertSee('Barang masuk')
            ->assertSee('Dikonfirmasi oleh')
            ->assertSee('Catatan penerimaan')
            ->assertDontSee('Rp 15.000')
            ->assertDontSee('Rp 25.000');
    }

    public function test_admin_can_see_product_prices_and_missing_products_return_not_found(): void
    {
        $admin = User::factory()->admin()->create();
        $productId = $this->createProduct();

        $this->actingAs($admin)
            ->get(route('produk.show', $productId))
            ->assertOk()
            ->assertSee('Rp 15.000')
            ->assertSee('Rp 25.000')
            ->assertSee('Belum ada riwayat transaksi untuk produk ini.');

        $this->get(route('produk.show', 9999))->assertNotFound();
    }

    public function test_manager_can_save_custom_product_attributes_and_staff_can_see_them(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $categoryId = DB::table('categories')->insertGetId(['name' => 'Elektronik']);
        $supplierId = DB::table('suppliers')->insertGetId(['name' => 'Supplier Utama']);

        $this->actingAs($manager)
            ->post(route('produk.store'), [
                'product_code' => 'EL-200',
                'name' => 'Headset',
                'category_id' => $categoryId,
                'supplier_id' => $supplierId,
                'description' => 'Headset kabel.',
                'purchase_price' => 50000,
                'selling_price' => 75000,
                'stock' => 8,
                'min_stock' => 2,
                'attributes' => [
                    ['name' => 'Warna', 'value' => 'Hitam'],
                    ['name' => 'Berat', 'value' => '250 gram'],
                    ['name' => '', 'value' => ''],
                ],
            ])
            ->assertRedirect(route('produk.index'));

        $productId = (int) DB::table('products')->where('product_code', 'EL-200')->value('id');
        $this->assertDatabaseHas('product_attributes', [
            'product_id' => $productId,
            'attribute_name' => 'Warna',
            'attribute_value' => 'Hitam',
        ]);
        $this->assertDatabaseHas('product_attributes', [
            'product_id' => $productId,
            'attribute_name' => 'Berat',
            'attribute_value' => '250 gram',
        ]);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_STAFF]))
            ->get(route('produk.show', $productId))
            ->assertOk()
            ->assertSee('Warna')
            ->assertSee('Hitam')
            ->assertSee('Berat')
            ->assertSee('250 gram');
    }

    public function test_manager_can_create_product_without_a_description(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $categoryId = DB::table('categories')->insertGetId(['name' => 'Elektronik']);
        $supplierId = DB::table('suppliers')->insertGetId(['name' => 'Supplier Utama']);

        $this->actingAs($manager)
            ->post(route('produk.store'), [
                'product_code' => 'EL-201',
                'name' => 'USB Hub',
                'category_id' => $categoryId,
                'supplier_id' => $supplierId,
                'purchase_price' => 30000,
                'selling_price' => 45000,
                'stock' => 6,
                'min_stock' => 2,
            ])
            ->assertRedirect(route('produk.index'));

        $this->assertDatabaseHas('products', [
            'product_code' => 'EL-201',
            'name' => 'USB Hub',
            'description' => '-',
        ]);
    }

    public function test_admin_can_replace_product_attributes_and_duplicate_names_are_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $productId = $this->createProduct();
        DB::table('product_attributes')->insert([
            'product_id' => $productId,
            'attribute_name' => 'Warna',
            'attribute_value' => 'Hitam',
        ]);

        $this->actingAs($admin)
            ->get(route('produk.edit', $productId))
            ->assertOk()
            ->assertJsonPath('attributes.0.name', 'Warna')
            ->assertJsonPath('attributes.0.value', 'Hitam');

        $this->put(route('produk.update', $productId), [
            'product_code' => 'EL-100',
            'name' => 'Mouse Wireless',
            'category_id' => 1,
            'supplier_id' => 1,
            'description' => 'Mouse diperbarui.',
            'purchase_price' => 15000,
            'selling_price' => 25000,
            'stock' => 32,
            'min_stock' => 5,
            'attributes' => [
                ['name' => 'Warna', 'value' => 'Putih'],
                ['name' => 'Ukuran', 'value' => 'Kecil'],
            ],
        ])->assertRedirect(route('produk.index'));

        $this->assertDatabaseMissing('product_attributes', [
            'product_id' => $productId,
            'attribute_name' => 'Warna',
            'attribute_value' => 'Hitam',
        ]);
        $this->assertDatabaseHas('product_attributes', [
            'product_id' => $productId,
            'attribute_name' => 'Warna',
            'attribute_value' => 'Putih',
        ]);
        $this->assertDatabaseHas('product_attributes', [
            'product_id' => $productId,
            'attribute_name' => 'Ukuran',
            'attribute_value' => 'Kecil',
        ]);

        $this->from(route('produk.index'))
            ->put(route('produk.update', $productId), [
                'product_code' => 'EL-100',
                'name' => 'Mouse Wireless',
                'category_id' => 1,
                'supplier_id' => 1,
                'description' => 'Mouse diperbarui.',
                'purchase_price' => 15000,
                'selling_price' => 25000,
                'stock' => 32,
                'min_stock' => 5,
                'attributes' => [
                    ['name' => 'Warna', 'value' => 'Hitam'],
                    ['name' => 'warna', 'value' => 'Putih'],
                ],
            ])
            ->assertSessionHasErrors('attributes.1.name');

        $this->assertDatabaseHas('product_attributes', [
            'product_id' => $productId,
            'attribute_name' => 'Warna',
            'attribute_value' => 'Putih',
        ]);
    }

    private function createProduct(): int
    {
        $categoryId = DB::table('categories')->insertGetId(['name' => 'Elektronik']);
        $supplierId = DB::table('suppliers')->insertGetId(['name' => 'Supplier Utama']);

        return DB::table('products')->insertGetId([
            'product_code' => 'EL-100',
            'name' => 'Mouse Wireless',
            'category_id' => $categoryId,
            'supplier_id' => $supplierId,
            'description' => 'Mouse untuk pengujian halaman detail.',
            'purchase_price' => 15000,
            'selling_price' => 25000,
            'stock' => 32,
            'min_stock' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createTransaction(int $productId, int $managerId, int $staffId): void
    {
        DB::table('transactions')->insert([
            'product_id' => $productId,
            'user_id' => $managerId,
            'assigned_to' => $staffId,
            'confirmed_by' => $staffId,
            'type' => 'in',
            'quantity' => 5,
            'current_stock' => 32,
            'status' => 'completed',
            'confirmed_at' => now(),
            'notes' => 'Catatan penerimaan',
            'created_at' => now()->subMinute(),
            'updated_at' => now(),
        ]);
    }
}
