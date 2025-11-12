<?php

namespace BusinessLogicECS\Systems;

use ECS\ListIteratingSystem;
use BusinessLogicECS\Nodes\OrderProcessingNode;

class OrderCompletionSystem extends ListIteratingSystem
{
    public function __construct()
    {
        parent::__construct(OrderProcessingNode::class, [$this, 'updateNode']);
    }

    public function updateNode(OrderProcessingNode $node, float $time): void
    {
        if ($node->order->status === 'inventory_reserved') {
            $node->order->status = 'completed';
            echo "Order {" . $node->order->orderId . "} : Order completed. Total: " . $node->price->totalPrice . "\n";
        } elseif ($node->order->status === 'cancelled_no_stock') {
            echo "Order {" . $node->order->orderId . "} : Order remains cancelled due to insufficient stock.\n";
        }
    }
}

