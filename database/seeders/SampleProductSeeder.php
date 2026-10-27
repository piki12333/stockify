<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SampleProductSeeder extends Seeder
{
    public function run(): void
    {
        $categoryIds = DB::table('categories')->pluck('id', 'name');
        $supplierIds = DB::table('suppliers')->pluck('id', 'name');

        $products = [
            ['Elektronik', 'PT. Indotekno Elektronik', 'Mouse Wireless', 'Mouse wireless 2.4 GHz dengan penerima USB.', 85000, 125000, 32, 5],
            ['Elektronik', 'PT. Indotekno Elektronik', 'Keyboard USB', 'Keyboard USB layout Indonesia untuk komputer.', 110000, 159000, 24, 5],
            ['Elektronik', 'PT. Global Tech Abadi', 'Kabel HDMI 2 Meter', 'Kabel HDMI untuk monitor dan perangkat multimedia.', 42000, 65000, 45, 8],
            ['Elektronik', 'PT. Global Tech Abadi', 'Adaptor USB-C 20W', 'Adaptor pengisi daya USB-C daya 20 watt.', 95000, 139000, 18, 4],
            ['Komputer', 'CV. Sentosa Komputer', 'SSD SATA 256GB', 'Solid state drive SATA kapasitas 256 GB.', 325000, 399000, 12, 3],
            ['Komputer', 'CV. Sentosa Komputer', 'RAM DDR4 8GB', 'Modul memori DDR4 kapasitas 8 GB.', 245000, 315000, 16, 4],
            ['Komputer', 'PT. Indotekno Elektronik', 'Flashdisk 64GB', 'Media penyimpanan USB kapasitas 64 GB.', 65000, 89000, 40, 8],
            ['Komputer', 'PT. Global Tech Abadi', 'Webcam HD', 'Webcam resolusi HD dengan mikrofon internal.', 175000, 229000, 11, 3],
            ['Aksesoris', 'CV. Gadget Makmur', 'Mouse Pad', 'Alas mouse berbahan kain dengan dasar antiselip.', 18000, 30000, 55, 10],
            ['Aksesoris', 'CV. Gadget Makmur', 'Headset Kabel', 'Headset kabel dengan mikrofon untuk penggunaan harian.', 78000, 115000, 21, 5],
            ['Aksesoris', 'PT. Indotekno Elektronik', 'Stand Laptop', 'Penyangga laptop lipat berbahan aluminium.', 92000, 139000, 14, 4],
            ['Gadget', 'CV. Gadget Makmur', 'Power Bank 10000mAh', 'Power bank kapasitas 10.000 mAh dengan port USB.', 210000, 279000, 9, 3],
            ['Gadget', 'PT. Global Tech Abadi', 'Kabel Data USB-C', 'Kabel pengisian daya dan transfer data USB-C.', 25000, 42000, 60, 12],
            ['Gadget', 'CV. Gadget Makmur', 'Holder Ponsel', 'Dudukan ponsel meja dengan sudut yang dapat diatur.', 35000, 55000, 27, 6],
            ['Makanan', 'CV. Sentosa Komputer', 'Biskuit Cokelat', 'Biskuit rasa cokelat kemasan 150 gram.', 9000, 14000, 75, 15],
            ['Makanan', 'CV. Sentosa Komputer', 'Keripik Singkong', 'Keripik singkong kemasan 100 gram.', 8000, 12500, 64, 12],
            ['Minuman', 'CV. Sentosa Komputer', 'Teh Botol 350ml', 'Minuman teh siap konsumsi kemasan botol 350 ml.', 4500, 7000, 90, 20],
            ['Minuman', 'CV. Sentosa Komputer', 'Air Mineral 600ml', 'Air mineral dalam kemasan botol 600 ml.', 2500, 4000, 120, 24],
            ['jamu', 'CV. Sentosa Komputer', 'Jamu Kunyit Asam', 'Minuman jamu kunyit asam kemasan 250 ml.', 7000, 11000, 35, 8],
            ['jamu', 'CV. Sentosa Komputer', 'Jamu Beras Kencur', 'Minuman jamu beras kencur kemasan 250 ml.', 7000, 11000, 29, 8],
        ];

        $now = now();
        $inserted = 0;

        DB::transaction(function () use ($products, $categoryIds, $supplierIds, $now, &$inserted): void {
            foreach ($products as $index => [$categoryName, $supplierName, $name, $description, $purchasePrice, $sellingPrice, $stock, $minimumStock]) {
                if (! isset($categoryIds[$categoryName])) {
                    throw new RuntimeException("Kategori contoh produk tidak ditemukan: {$categoryName}");
                }

                if (! isset($supplierIds[$supplierName])) {
                    throw new RuntimeException("Supplier contoh produk tidak ditemukan: {$supplierName}");
                }

                $productCode = sprintf('DEMO-%03d', $index + 1);
                if (DB::table('products')->where('product_code', $productCode)->exists()) {
                    continue;
                }

                DB::table('products')->insert([
                    'product_code' => $productCode,
                    'category_id' => $categoryIds[$categoryName],
                    'supplier_id' => $supplierIds[$supplierName],
                    'name' => $name,
                    'description' => $description,
                    'purchase_price' => $purchasePrice,
                    'selling_price' => $sellingPrice,
                    'stock' => $stock,
                    'min_stock' => $minimumStock,
                    'image_url' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $inserted++;
            }
        });

        $this->command?->info("Berhasil menambahkan {$inserted} produk contoh.");
    }
}
