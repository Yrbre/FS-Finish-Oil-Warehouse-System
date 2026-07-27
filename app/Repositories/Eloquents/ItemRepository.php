<?php

namespace App\Repositories\Eloquents;

use App\Models\Item;
use App\Repositories\Interfaces\ItemRepositoryInterface;

class ItemRepository implements ItemRepositoryInterface
{
    protected Item $model;
    public function __construct(Item $item)
    {
        $this->model = $item;
    }

    public function getAll()
    {
        return $this->model->query();
    }

    public function getById(int $id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data)
    {
        $item = $this->getById($id);
        $item->update($data);
        return $item;
    }

    public function delete(int $id)
    {
        $item = $this->getById($id);
        $item->delete();
    }
}
