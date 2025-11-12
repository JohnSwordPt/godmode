<?php

namespace BusinessLogicECS\Components;

class PriceComponent
{
    public float $unitPrice;
    public int $quantity;
    public float $totalPrice;

    public function __construct(float $unitPrice, int $quantity)
    {
        $this->unitPrice = $unitPrice;
        $this->quantity = $quantity;
        $this->totalPrice = 0.0; // Will be calculated by a system
    }
}
