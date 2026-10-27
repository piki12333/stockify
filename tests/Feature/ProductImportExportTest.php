<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductImportExportTest extends TestCase
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
            $table->string('name')->unique();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->text('info')->nullable();
            $table->timestamps();
        });
        Schema::create('products', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('product_code', 50)->nullable()->unique();
            $table->string('name', 150);
            $table->unsignedInteger('category_id');
            $table->unsignedInteger('supplier_id');
            $table->text('description');
            $table->decimal('purchase_price', 15, 2);
            $table->decimal('selling_price', 15, 2);
            $table->integer('stock')->default(0);
            $table->integer('min_stock')->default(0);
            $table->string('image_url')->nullable();
            $table->timestamps();
        });
    }

    public function test_admin_can_import_products_and_export_them_as_csv(): void
    {
        $admin = User::factory()->admin()->create();
        DB::table('categories')->insert(['name' => 'Elektronik']);
        DB::table('suppliers')->insert(['name' => 'Supplier Utama']);
        $csv = implode("\n", [
            'product_code,name,category,supplier,description,purchase_price,selling_price,stock,min_stock,image_url',
            'EL-100,Kabel USB,Elektronik,Supplier Utama,Kabel data,15000,25000,20,3,',
        ]);

        $this->actingAs($admin)
            ->post(route('produk.import'), [
                'file' => UploadedFile::fake()->createWithContent('produk.csv', $csv),
            ])
            ->assertRedirect(route('produk.index'))
            ->assertSessionHas('success', '1 produk berhasil diimpor.');

        $product = DB::table('products')->where('product_code', 'EL-100')->first();
        $this->assertNotNull($product);
        $this->assertSame(20, (int) $product->stock);
        $this->assertSame(1, (int) $product->category_id);
        $this->assertSame(1, (int) $product->supplier_id);

        $export = $this->get(route('produk.export'));
        $export->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('product_code,name,category,supplier', $export->streamedContent());
        $this->assertStringContainsString('EL-100', $export->streamedContent());
        $this->assertStringContainsString('Elektronik', $export->streamedContent());
        $this->assertStringContainsString('Supplier Utama', $export->streamedContent());
    }

    public function test_import_rejects_invalid_rows_without_partially_saving_products(): void
    {
        $admin = User::factory()->admin()->create();
        DB::table('categories')->insert(['name' => 'Elektronik']);
        DB::table('suppliers')->insert(['name' => 'Supplier Utama']);
        $csv = implode("\n", [
            'product_code,name,category,supplier,description,purchase_price,selling_price,stock,min_stock,image_url',
            'EL-101,Kabel USB,Elektronik,Supplier Utama,Kabel data,15000,25000,20,3,',
            'EL-102,Mouse,Kategori Tidak Ada,Supplier Utama,Mouse,10000,20000,5,1,',
        ]);

        $this->actingAs($admin)
            ->from(route('produk.index'))
            ->post(route('produk.import'), [
                'file' => UploadedFile::fake()->createWithContent('produk.csv', $csv),
            ])
            ->assertRedirect(route('produk.index'))
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_product_attributes_can_be_imported_and_exported_as_json(): void
    {
        $admin = User::factory()->admin()->create();
        DB::table('categories')->insert(['name' => 'Elektronik']);
        DB::table('suppliers')->insert(['name' => 'Supplier Utama']);
        $csv = implode("\n", [
            'product_code,name,category,supplier,description,purchase_price,selling_price,stock,min_stock,image_url,attributes',
            'EL-200,Mouse,Elektronik,Supplier Utama,Mouse,10000,20000,3,1,,"[{""name"":""Warna"",""value"":""Hitam""}]"',
        ]);

        $this->actingAs($admin)
            ->post(route('produk.import'), [
                'file' => UploadedFile::fake()->createWithContent('produk-atribut.csv', $csv),
            ])
            ->assertRedirect(route('produk.index'))
            ->assertSessionHas('success', '1 produk berhasil diimpor.');

        $productId = (int) DB::table('products')->where('product_code', 'EL-200')->value('id');
        $this->assertDatabaseHas('product_attributes', [
            'product_id' => $productId,
            'attribute_name' => 'Warna',
            'attribute_value' => 'Hitam',
        ]);

        $exportedCsv = $this->get(route('produk.export'))->streamedContent();
        $this->assertStringContainsString('image_url,attributes', $exportedCsv);
        $this->assertStringContainsString('Warna', $exportedCsv);
        $this->assertStringContainsString('Hitam', $exportedCsv);
    }

    public function test_only_admin_can_import_or_export_products(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $this->actingAs($manager);

        $this->get(route('produk.index'))
            ->assertOk()
            ->assertDontSee('Ekspor CSV')
            ->assertDontSee('Impor data produk');
        $this->get(route('produk.export'))->assertForbidden();
        $this->post(route('produk.import'), [
            'file' => UploadedFile::fake()->createWithContent(
                'produk.csv',
                'product_code,name,category,supplier,description,purchase_price,selling_price,stock,min_stock,image_url'
            ),
        ])->assertForbidden();
    }

    public function test_admin_sees_product_import_and_export_controls(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('produk.index'))
            ->assertOk()
            ->assertSee('Ekspor CSV')
            ->assertSee('Impor data produk')
            ->assertSee('maksimal 1.000 produk', false);
    }
}
