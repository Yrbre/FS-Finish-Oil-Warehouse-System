<?php

namespace App\Services;

use App\Repositories\Interfaces\ItemRepositoryInterface;
use App\Services\Interfaces\ItemServiceInterface;

class ItemService implements ItemServiceInterface
{
    protected ItemRepositoryInterface $itemRepository;

    public function __construct(ItemRepositoryInterface $itemRepository)
    {
        $this->itemRepository = $itemRepository;
    }

    public function getAll()
    {
        return $this->itemRepository->getAll();
    }

    public function getById(int $id)
    {
        return $this->itemRepository->getById($id);
    }

    public function create(array $data)
    {
        return $this->itemRepository->create($data);
    }

    public function update(int $id, array $data)
    {
        return $this->itemRepository->update($id, $data);
    }

    public function delete(int $id)
    {
        $item = $this->itemRepository->getById($id);

        // Cegah hapus item yang stoknya masih ada di gudang manapun
        $hasStock = $item->itemLocations()->where('qty_weight', '>', 0)->exists();

        if ($hasStock) {
            throw new \Exception("Item masih memiliki stok di gudang, tidak dapat dihapus.");
        }

        return $this->itemRepository->delete($id);
    }
}
