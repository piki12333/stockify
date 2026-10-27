<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('name');
            });
        }

        if (! Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('product_code')->nullable();
                $table->string('name');
                $table->unsignedInteger('category_id');
                $table->integer('stock')->default(0);
                $table->integer('min_stock')->default(0);
                $table->timestamps();
            });
        }
    }

    public function test_stock_report_calculates_period_movements_and_exports_csv(): void
    {
        $user = User::factory()->admin()->create();
        $categoryId = DB::table('categories')->insertGetId(['name' => 'Elektronik']);
        $productId = DB::table('products')->insertGetId([
            'product_code' => 'EL-01',
            'name' => 'Kabel Data',
            'category_id' => $categoryId,
            'stock' => 20,
            'min_stock' => 3,
            'created_at' => now()->subDays(20),
            'updated_at' => now()->subDays(20),
        ]);
        $start = now()->subDays(10)->toDateString();
        $end = now()->subDays(4)->toDateString();
        DB::table('transactions')->insert([
            'product_id' => $productId,
            'type' => 'in',
            'quantity' => 5,
            'notes' => 'Restock',
            'created_at' => now()->subDays(7),
            'updated_at' => now()->subDays(7),
        ]);

        $this->actingAs($user)
            ->get(route('reports.index', [
                'report' => 'stock',
                'start_date' => $start,
                'end_date' => $end,
                'category_id' => $categoryId,
            ]))
            ->assertOk()
            ->assertSee('Kabel Data')
            ->assertSee('Elektronik')
            ->assertSee('15')
            ->assertSee('20');

        $export = $this->get(route('reports.export', [
            'report' => 'stock',
            'start_date' => $start,
            'end_date' => $end,
        ]));

        $export->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('laporan-stock-', $export->headers->get('content-disposition'));
        $this->assertStringContainsString('Stok Awal', $export->streamedContent());
    }

    public function test_transaction_and_user_activity_reports_are_available(): void
    {
        $user = User::factory()->admin()->create();
        $categoryId = DB::table('categories')->insertGetId(['name' => 'Aksesoris']);
        $productId = DB::table('products')->insertGetId([
            'product_code' => 'AK-02',
            'name' => 'Mouse',
            'category_id' => $categoryId,
            'stock' => 8,
            'min_stock' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('transactions')->insert([
            'product_id' => $productId,
            'type' => 'out',
            'quantity' => 2,
            'notes' => 'Pesanan pelanggan',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user->activities()->create([
            'event' => 'profile-updated',
            'description' => 'Mengubah informasi profil.',
            'ip_address' => '127.0.0.1',
        ]);
        User::factory()->create()->activities()->create([
            'event' => 'password-updated',
            'description' => 'Aktivitas rahasia pengguna lain.',
        ]);

        $this->actingAs($user)
            ->get(route('reports.index', ['report' => 'transactions']))
            ->assertOk()
            ->assertSee('Mouse')
            ->assertSee('Pesanan pelanggan')
            ->assertSee('Barang keluar');

        $this->get(route('reports.index', ['report' => 'activities']))
            ->assertOk()
            ->assertSee($user->name)
            ->assertSee('Mengubah informasi profil.')
            ->assertSee('Aktivitas rahasia pengguna lain.');
    }
}
