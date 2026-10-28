<?php

namespace App\Services;

use App\Repositories\StockOpnameRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StockOpnameService
{
    public function __construct(
        private readonly StockOpnameRepository $repository
    ) {}

    public function getFilteredOpnames(?string $search, ?string $startDate, ?string $endDate): Collection
    {
        return $this->repository->getFiltered($search, $startDate, $endDate);
    }

    public function getAllProducts(): Collection
    {
        return $this->repository->getAllProducts();
    }

    /**
     * Ajukan selisih stock opname baru.
     */
    public function submitOpname(int $userId, array $data): int
    {
        $product = $this->repository->findProduct((int) $data['product_id']);
        abort_if(! $product, 404, 'Produk tidak ditemukan.');

        $systemStock = (int) $product->stock;
        $physicalStock = (int) $data['physical_stock'];
        $difference = $physicalStock - $systemStock;

        return $this->repository->create([
            'product_id' => $product->id,
            'user_id' => $userId,
            'system_stock' => $systemStock,
            'physical_stock' => $physicalStock,
            'difference' => $difference,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Setujui pengajuan stock opname dan sesuaikan stok produk secara atomik.
     */
    public function approveOpname(int $reviewerId, int $opnameId, ?string $reviewNotes): void
    {
        DB::transaction(function () use ($reviewerId, $opnameId, $reviewNotes): void {
            $opname = $this->repository->findForUpdate($opnameId);
            abort_if(! $opname, 404, 'Pengajuan tidak ditemukan.');

            abort_if($opname->status !== 'pending', 422, 'Pengajuan ini sudah ditinjau.');
            abort_if((int) $opname->user_id === $reviewerId, 403, 'Anda tidak dapat menyetujui pengajuan sendiri.');

            $product = $this->repository->findProductForUpdate((int) $opname->product_id);
            abort_if(! $product, 404, 'Produk terkait tidak ditemukan.');

            $difference = (int) $opname->physical_stock - (int) $product->stock;

            // Perbarui stok master produk sesuai hasil hitung fisik
            $this->repository->updateProductStock((int) $product->id, (int) $opname->physical_stock);

            // Perbarui status pengajuan menjadi approved
            $this->repository->update((int) $opname->id, [
                'system_stock' => $product->stock,
                'difference' => $difference,
                'status' => 'approved',
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
                'review_notes' => $reviewNotes,
            ]);
        });
    }

    /**
     * Tolak pengajuan stock opname tanpa mengubah stok produk.
     */
    public function rejectOpname(int $reviewerId, int $opnameId, string $reviewNotes): void
    {
        DB::transaction(function () use ($reviewerId, $opnameId, $reviewNotes): void {
            $opname = $this->repository->findForUpdate($opnameId);
            abort_if(! $opname, 404, 'Pengajuan tidak ditemukan.');

            abort_if($opname->status !== 'pending', 422, 'Pengajuan ini sudah ditinjau.');
            abort_if((int) $opname->user_id === $reviewerId, 403, 'Anda tidak dapat menolak pengajuan sendiri.');

            $this->repository->update((int) $opname->id, [
                'status' => 'rejected',
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
                'review_notes' => $reviewNotes,
            ]);
        });
    }

    /**
     * Hapus riwayat stock opname.
     */
    public function deleteOpname(int $id): int
    {
        return $this->repository->delete($id);
    }
}
