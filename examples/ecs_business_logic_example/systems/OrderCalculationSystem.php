<?php

namespace BusinessLogicECS\Systems;

use ECS\ListIteratingSystem;
use BusinessLogicECS\Nodes\OrderProcessingNode;

class OrderCalculationSystem extends ListIteratingSystem
{
    public function __construct()
    {
        parent::__construct(OrderProcessingNode::class, [$this, 'updateNode']);
    }

    public function updateNode(OrderProcessingNode $node, float $time): void
    {
        if ($node->order->status === 'pending') {
            // For simplicity, let's assume each item in the order has a corresponding price component
            // In a real scenario, you'd fetch product prices from a database or service
            $totalOrderPrice = 0.0;
            foreach ($node->order->items as $item) {
                // This is a simplified example. In a real app, you'd match item to its price component
                // For now, let's assume price component directly relates to the order's total
                // This system will calculate the total price for the entire order.
                // The PriceComponent here is assumed to be for the *entire* order for simplicity.
                // A more complex system would have PriceComponents per item.
                $totalOrderPrice += $node->price->unitPrice * $item['quantity'];
            }
            $node->price->totalPrice = $totalOrderPrice;
            echo "Order {$node->order->orderId}: Calculated total price {$node->price->totalPrice}\n";
            $node->order->status = 'calculated';
        }
    }
}

