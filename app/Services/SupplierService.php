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
        ]);
    }

    public function updateSupplier(int $id, array $data): int
    {
        return $this->repository->update($id, [
            'name' => $data['name'],
            'phone' => $data['phone'] ?? '-',
            'address' => $data['address'] ?? '-',
        ]);
    }

    public function deleteSupplier(int $id): int
    {
        return $this->repository->delete($id);
    }
}

