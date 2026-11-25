<?php

namespace App\Services;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CategoryService
{
    public function __construct(
        private readonly CategoryRepository $repository
    ) {}

    public function getPaginatedCategories(?string $search, int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->paginate($search, $perPage);
    }

    public function createCategory(array $data): Category
    {
        return $this->repository->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);
    }

    public function updateCategory(int $id, array $data): bool
    {
        return $this->repository->update($id, [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);
    }

    public function deleteCategory(int $id): int
    {
        return $this->repository->delete($id);
    }
}
