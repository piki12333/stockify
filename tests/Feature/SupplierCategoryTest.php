<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SupplierCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('categories', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });
        Schema::create('suppliers', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name', 100);
            $table->string('phone', 50);
            $table->text('address');
            $table->text('info');
            $table->timestamps();
        });
        Schema::create('products', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('category_id');
            $table->unsignedInteger('supplier_id');
        });
    }

    public function test_admin_can_create_supplier_with_selected_categories(): void
    {
        $admin = User::factory()->admin()->create();
        $categoryId = DB::table('categories')->insertGetId(['name' => 'Elektronik']);

        $this->actingAs($admin)
            ->post(route('suppliers.store'), [
                'name' => 'Supplier Elektronik',
                'phone' => '08123456789',
                'address' => 'Jakarta',
                'category_ids' => [$categoryId],
            ])
            ->assertRedirect(route('suppliers.index'))
            ->assertSessionHas('success', 'Supplier berhasil ditambahkan!');

        $this->assertDatabaseHas('suppliers', [
            'name' => 'Supplier Elektronik',
            'info' => '-',
        ]);
        $this->assertDatabaseHas('supplier_categories', [
            'supplier_id' => 1,
            'category_id' => $categoryId,
        ]);

        $this->get(route('suppliers.edit', 1))
            ->assertOk()
            ->assertJsonPath('category_ids.0', $categoryId);
        $this->get(route('suppliers.index'))
            ->assertOk()
            ->assertSee('Supplier Elektronik')
            ->assertSee('Elektronik');
    }

    public function test_category_in_use_by_products_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $categoryId = DB::table('categories')->insertGetId(['name' => 'Elektronik']);
        DB::table('products')->insert([
            'category_id' => $categoryId,
            'supplier_id' => 1,
        ]);

        $this->actingAs($admin)
            ->delete(route('categories.destroy', $categoryId))
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('error', 'Kategori tidak bisa dihapus karena masih digunakan oleh 1 produk. Pindahkan atau hapus produk tersebut terlebih dahulu.');

        $this->assertDatabaseHas('categories', ['id' => $categoryId]);
    }
}
