<?php

namespace App\Services;

use App\Repositories\SupplierRepository;
use Illuminate\Support\Collection;

class SupplierService
{
    public function __construct(
        private readonly SupplierRepository $repository
    ) {}

    public function getAllSuppliers(): Collection
    {
        return $this->repository->getAll();
    }

    public function getAllCategories(): Collection
    {
        return $this->repository->getCategories();
    }

    public function getSupplierById(int $id): ?object
    {
        return $this->repository->findById($id);
    }

    public function createSupplier(array $data): int
    {
        return $this->repository->create([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? '-',
            'address' => $data['address'] ?? '-',
            'info' => $data['info'] ?? '-',
            'category_ids' => $data['category_ids'] ?? [],
        ]);
    }

    public function updateSupplier(int $id, array $data): int
    {
        return $this->repository->update($id, [
            'name' => $data['name'],
            'phone' => $data['phone'] ?? '-',
            'address' => $data['address'] ?? '-',
            'info' => $data['info'] ?? '-',
            'category_ids' => $data['category_ids'] ?? [],
        ]);
    }

    public function deleteSupplier(int $id): bool
    {
        return $this->repository->delete($id);
    }
}
