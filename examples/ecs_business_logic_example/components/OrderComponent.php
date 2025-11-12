<?php

namespace BusinessLogicECS\Components;

class OrderComponent
{
    public string $orderId;
    public string $customerName;
    public string $status; // e.g., 'pending', 'processing', 'completed', 'cancelled'
    public array $items; // e.g., [['productId' => 'P1', 'quantity' => 2]]

    public function __construct(string $orderId, string $customerName, array $items)
    {
        $this->orderId = $orderId;
        $this->customerName = $customerName;
        $this->items = $items;
        $this->status = 'pending';
    }
}
