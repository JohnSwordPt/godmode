<?php

namespace BusinessLogicECS\Components;

class InventoryComponent
{
    public string $productId;
    public int $stockLevel;

    public function __construct(string $productId, int $stockLevel)
    {
        $this->productId = $productId;
        $this->stockLevel = $stockLevel;
    }
}
