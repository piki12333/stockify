<?php

namespace App\Services;

use App\Repositories\ProductRepository;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ProductService — logika bisnis terkait produk:
 * validasi, manipulasi gambar, import/export CSV, normalisasi atribut.
 *
 * Semua akses database diserahkan ke ProductRepository.
 */
class ProductService
{
    public function __construct(
        private readonly ProductRepository $repository
    ) {}

    // -------------------------------------------------------------------------
    // CRUD utama
    // -------------------------------------------------------------------------

    /**
     * Simpan produk baru beserta gambar dan atributnya dalam satu transaksi DB.
     */
    public function store(array $validated, ?UploadedFile $imageFile): void
    {
        $attributes = $this->normalizeAttributes($validated['attributes'] ?? []);
        $imagePath  = $imageFile ? $imageFile->store('products', 'public') : null;

        DB::transaction(function () use ($validated, $imagePath, $attributes): void {
            $productId = $this->repository->create([
                'product_code'  => $validated['product_code'] ?? null,
                'name'          => $validated['name'],
                'category_id'   => $validated['category_id'],
                'supplier_id'   => $validated['supplier_id'],
                'description'   => $validated['description'] ?? '-',
                'purchase_price'=> $validated['purchase_price'] ?? 0,
                'selling_price' => $validated['selling_price'],
                'stock'         => $validated['stock'],
                'min_stock'     => $validated['min_stock'] ?? 0,
                'image_url'     => $imagePath,
            ]);

            $this->repository->saveAttributes($productId, $attributes);
        });
    }

    /**
     * Perbarui produk berikut gambar dan atributnya.
     */
    public function update(int $id, array $validated, ?UploadedFile $imageFile): void
    {
        $product    = $this->repository->findRaw($id);
        abort_if($product === null, 404, 'Produk tidak ditemukan.');

        $attributes = $this->normalizeAttributes($validated['attributes'] ?? []);
        $imagePath  = $product->image_url;

        if ($imageFile !== null) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = $imageFile->store('products', 'public');
        }

        DB::transaction(function () use ($id, $validated, $imagePath, $attributes): void {
            $this->repository->update($id, [
                'product_code'  => $validated['product_code'] ?? null,
                'name'          => $validated['name'],
                'category_id'   => $validated['category_id'],
                'supplier_id'   => $validated['supplier_id'],
                'description'   => $validated['description'] ?? '-',
                'purchase_price'=> $validated['purchase_price'] ?? 0,
                'selling_price' => $validated['selling_price'],
                'stock'         => $validated['stock'],
                'min_stock'     => $validated['min_stock'] ?? 0,
                'image_url'     => $imagePath,
            ]);

            $this->repository->deleteAttributes($id);
            $this->repository->saveAttributes($id, $attributes);
        });
    }

    /**
     * Hapus satu produk beserta gambar dan atributnya.
     */
    public function destroy(int $id): void
    {
        $product = $this->repository->findRaw($id);
        if ($product && $product->image_url) {
            Storage::disk('public')->delete($product->image_url);
        }
        $this->repository->deleteAttributes($id);
        $this->repository->delete($id);
    }

    /**
     * Hapus banyak produk sekaligus.
     *
     * @param  int[]  $ids
     */
    public function bulkDestroy(array $ids): void
    {
        $products = $this->repository->findManyRaw($ids);
        foreach ($products as $product) {
            if ($product->image_url) {
                Storage::disk('public')->delete($product->image_url);
            }
        }
        $this->repository->deleteAttributesForMany($ids);
        $this->repository->deleteMany($ids);
    }

    // -------------------------------------------------------------------------
    // Export
    // -------------------------------------------------------------------------

    /**
     * Buat response streaming CSV untuk seluruh produk.
     * Menggunakan cursor agar hemat memori untuk data besar.
     */
    public function exportCsv(): StreamedResponse
    {
        $products            = $this->repository->cursorForExport();
        $attributesByProduct = $this->repository->allAttributesGrouped();

        return response()->streamDownload(
            function () use ($products, $attributesByProduct): void {
                $stream = fopen('php://output', 'w');
                if ($stream === false) {
                    throw new \RuntimeException('Gagal membuka keluaran CSV produk.');
                }

                fwrite($stream, "\xEF\xBB\xBF");
                fputcsv($stream, [
                    'product_code', 'name', 'category', 'supplier', 'description',
                    'purchase_price', 'selling_price', 'stock', 'min_stock',
                    'image_url', 'attributes',
                ]);

                foreach ($products as $product) {
                    $attrs = $attributesByProduct->get($product->id, collect())
                        ->map(static fn (object $a): array => [
                            'name'  => $a->attribute_name,
                            'value' => $a->attribute_value,
                        ])
                        ->values()
                        ->all();

                    fputcsv($stream, array_map($this->safeCsvValue(...), [
                        $product->product_code,
                        $product->name,
                        $product->category_name,
                        $product->supplier_name,
                        $product->description,
                        $product->purchase_price,
                        $product->selling_price,
                        $product->stock,
                        $product->min_stock,
                        $product->image_url,
                        json_encode($attrs, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    ]));
                }

                fclose($stream);
            },
            'produk-' . now()->format('Ymd-His') . '.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8']
        );
    }

    // -------------------------------------------------------------------------
    // Import
    // -------------------------------------------------------------------------

    /**
     * Proses import CSV produk: validasi baris per baris lalu simpan dalam
     * satu transaksi DB.
     *
     * @throws ValidationException saat ada error pada berkas.
     */
    public function importCsv(UploadedFile $file): int
    {
        $stream = fopen($file->getRealPath(), 'r');
        if ($stream === false) {
            throw new \RuntimeException('Berkas CSV produk tidak dapat dibuka.');
        }

        $expectedHeaders    = ['product_code', 'name', 'category', 'supplier', 'description',
                                'purchase_price', 'selling_price', 'stock', 'min_stock', 'image_url'];
        $headersWithAttrs   = [...$expectedHeaders, 'attributes'];

        $headers = fgetcsv($stream);
        if ($headers === false) {
            fclose($stream);
            throw ValidationException::withMessages(['file' => 'Berkas CSV kosong atau tidak memiliki judul kolom.']);
        }

        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);
        $headers    = array_map(static fn ($h): string => strtolower(trim((string) $h)), $headers);

        if ($headers !== $expectedHeaders && $headers !== $headersWithAttrs) {
            fclose($stream);
            throw ValidationException::withMessages([
                'file' => 'Susunan kolom CSV tidak sesuai. Unduh format produk dari halaman ini, lalu gunakan kolom yang tersedia.',
            ]);
        }

        $hasAttrs     = $headers === $headersWithAttrs;
        $categories   = $this->repository->categoryNameMap();
        $suppliers    = $this->repository->supplierNameMap();
        $rows         = [];
        $errors       = [];
        $seenCodes    = [];
        $line         = 1;
        $dataRows     = 0;

        while (($values = fgetcsv($stream)) !== false) {
            $line++;
            if ($values === [null] || (count($values) === 1 && trim((string) $values[0]) === '')) {
                continue;
            }

            $dataRows++;
            if ($dataRows > 1000) {
                $errors[] = 'Maksimal 1.000 produk dapat diimpor dalam satu berkas.';
                break;
            }

            if (count($values) !== count($headers)) {
                $errors[] = "Baris {$line}: jumlah kolom tidak sesuai.";
                continue;
            }

            $row = array_combine($headers, array_map(
                static fn ($v): ?string => $v === null ? null : trim((string) $v),
                $values
            ));

            $validator = Validator::make($row, [
                'product_code'  => ['nullable', 'string', 'max:50', Rule::unique('products', 'product_code')],
                'name'          => ['required', 'string', 'max:150'],
                'category'      => ['required', 'string', 'max:100'],
                'supplier'      => ['required', 'string', 'max:100'],
                'description'   => ['nullable', 'string'],
                'purchase_price'=> ['required', 'numeric', 'min:0'],
                'selling_price' => ['required', 'numeric', 'min:0'],
                'stock'         => ['required', 'integer', 'min:0'],
                'min_stock'     => ['nullable', 'integer', 'min:0'],
                'image_url'     => ['nullable', 'string', 'max:255'],
            ]);

            if ($validator->fails()) {
                $errors[] = "Baris {$line}: " . implode(' ', $validator->errors()->all());
                continue;
            }

            $rowAttrs = [];
            if ($hasAttrs && ($row['attributes'] ?? '') !== '') {
                try {
                    $decoded = json_decode($row['attributes'], true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException) {
                    $errors[] = "Baris {$line}: format JSON atribut tidak valid.";
                    continue;
                }

                if (! is_array($decoded) || ! array_is_list($decoded)) {
                    $errors[] = "Baris {$line}: atribut harus berupa daftar JSON.";
                    continue;
                }

                $attrValidator = Validator::make(['attributes' => $decoded], [
                    'attributes'         => 'array|max:30',
                    'attributes.*.name'  => 'required|string|max:50',
                    'attributes.*.value' => 'required|string|max:100',
                ]);

                if ($attrValidator->fails()) {
                    $errors[] = "Baris {$line}: " . implode(' ', $attrValidator->errors()->all());
                    continue;
                }

                try {
                    $rowAttrs = $this->normalizeAttributes($decoded);
                } catch (ValidationException $e) {
                    $errors[] = "Baris {$line}: " . implode(' ', array_merge(...array_values($e->errors())));
                    continue;
                }
            }

            $code = trim((string) ($row['product_code'] ?? ''));
            if ($code !== '' && isset($seenCodes[strtolower($code)])) {
                $errors[] = "Baris {$line}: kode produk '{$code}' muncul lebih dari sekali.";
                continue;
            }

            $categoryId = $categories->get(strtolower($row['category']));
            $supplierId = $suppliers->get(strtolower($row['supplier']));
            if ($categoryId === null || $supplierId === null) {
                $missing = [];
                if ($categoryId === null) $missing[] = "kategori '{$row['category']}' tidak ditemukan";
                if ($supplierId === null) $missing[] = "supplier '{$row['supplier']}' tidak ditemukan";
                $errors[] = "Baris {$line}: " . implode(' dan ', $missing) . '.';
                continue;
            }

            if ($code !== '') {
                $seenCodes[strtolower($code)] = true;
            }

            $productRow = [
                'product_code'  => $code !== '' ? $code : null,
                'name'          => $row['name'],
                'category_id'   => $categoryId,
                'supplier_id'   => $supplierId,
                'description'   => $row['description'] ?? '-',
                'purchase_price'=> $row['purchase_price'],
                'selling_price' => $row['selling_price'],
                'stock'         => $row['stock'],
                'min_stock'     => ($row['min_stock'] !== null && $row['min_stock'] !== '') ? $row['min_stock'] : 0,
                'image_url'     => $row['image_url'] ?: null,
                'created_at'    => now(),
                'updated_at'    => now(),
            ];

            if ($hasAttrs) {
                $productRow['_attributes'] = $rowAttrs;
            }

            $rows[] = $productRow;
        }

        fclose($stream);

        if ($rows === [] && $errors === []) {
            $errors[] = 'Tidak ada baris produk untuk diimpor.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages([
                'file' => implode(' ', array_slice($errors, 0, 10)),
            ]);
        }

        DB::transaction(function () use ($rows, $hasAttrs): void {
            if ($hasAttrs) {
                foreach ($rows as $row) {
                    $attrs = $row['_attributes'];
                    unset($row['_attributes']);
                    $productId = $this->repository->create($row);
                    $this->repository->saveAttributes($productId, $attrs);
                }

                return;
            }

            foreach (array_chunk($rows, 200) as $chunk) {
                DB::table('products')->insert($chunk);
            }
        });

        return count($rows);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Bersihkan dan validasi pasangan nama-nilai atribut; buang yang kosong.
     *
     * @param  array<int, array<string, string>>  $attributes
     * @return array<int, array{name: string, value: string}>
     *
     * @throws ValidationException
     */
    public function normalizeAttributes(array $attributes): array
    {
        $normalized = [];
        $names      = [];

        foreach ($attributes as $index => $attribute) {
            $name  = trim((string) ($attribute['name']  ?? ''));
            $value = trim((string) ($attribute['value'] ?? ''));

            if ($name === '' && $value === '') {
                continue;
            }

            if ($name === '') {
                throw ValidationException::withMessages([
                    "attributes.{$index}.name" => 'Nama atribut wajib diisi jika nilainya diisi.',
                ]);
            }

            if ($value === '') {
                throw ValidationException::withMessages([
                    "attributes.{$index}.value" => 'Nilai atribut wajib diisi jika namanya diisi.',
                ]);
            }

            $normalizedName = mb_strtolower($name);
            if (isset($names[$normalizedName])) {
                throw ValidationException::withMessages([
                    "attributes.{$index}.name" => 'Nama atribut tidak boleh duplikat.',
                ]);
            }

            $names[$normalizedName] = true;
            $normalized[]           = ['name' => $name, 'value' => $value];
        }

        return $normalized;
    }

    /**
     * Cegah nilai CSV ditafsirkan sebagai rumus oleh spreadsheet.
     */
    private function safeCsvValue(mixed $value): string
    {
        $value = (string) ($value ?? '');

        return preg_match('/^[\s]*[=+\-@]/u', $value) ? "'" . $value : $value;
    }
}

