<?php

namespace App\Infrastructure\Util;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

class ArrayableCollection
{
    private $items;

    public function __construct(array $items)
    {
        $this->items = new Collection($items);

        if ($this->items->first(function ($item) {
            return ! ($item instanceof Arrayable);
        })) {
            throw new \InvalidArgumentException('Only `' . Arrayable::class . '` items are allowed.');
        }
    }

    public function addItem(Arrayable $item): ArrayableCollection
    {
        $this->items->add($item);

        return $this;
    }

    public function getItems(): array
    {
        return $this->items->toArray();
    }
}
