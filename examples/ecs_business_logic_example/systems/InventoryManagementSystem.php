<?php

namespace BusinessLogicECS\Systems;

use ECS\Engine;
use ECS\ListIteratingSystem;
use BusinessLogicECS\Nodes\OrderProcessingNode;
use BusinessLogicECS\Components\InventoryComponent;

class InventoryManagementSystem extends ListIteratingSystem
{
    protected ?Engine $engine = null;

    public function __construct()
    {
        parent::__construct(OrderProcessingNode::class, [$this, 'updateNode']);
    }

    public function AddToEngine($engine)
    {
        parent::AddToEngine($engine);
        $this->engine = $engine;
    }

    public function RemoveFromEngine(Engine $engine)
    {
        parent::RemoveFromEngine($engine);
        $this->engine = null;
    }

    public function updateNode(OrderProcessingNode $node, float $time): void
    {
        if ($node->order->status === 'calculated') {
            $allInventoryEntities = $this->engine->Entities();
            $orderItems = $node->order->items;
            $canFulfill = true;

            // First, check if all items are in stock
            foreach ($orderItems as $item) {
                $foundInventory = false;
                foreach ($allInventoryEntities as $entity) {
                    $inventoryComponent = $entity->get(InventoryComponent::class);
                    if ($inventoryComponent && $inventoryComponent->productId === $item['productId']) {
                        $foundInventory = true;
                        if ($inventoryComponent->stockLevel < $item['quantity']) {
                            echo "Order {$node->order->orderId}: Insufficient stock for product {$item['productId']}. Current stock: {$inventoryComponent->stockLevel}, Requested: {$item['quantity']}\n";
                            $canFulfill = false;
                            break 2; // Break from both inner and outer loops
                        }
                        break;
                    }
                }
                if (!$foundInventory) {
                    echo "Order {$node->order->orderId}: Product {$item['productId']} not found in inventory.\n";
                    $canFulfill = false;
                    break;
                }
            }

            if ($canFulfill) {
                // If all items are in stock, proceed to reserve/deduct inventory
                foreach ($orderItems as $item) {
                    foreach ($allInventoryEntities as $entity) {
                        $inventoryComponent = $entity->get(InventoryComponent::class);
                        if ($inventoryComponent && $inventoryComponent->productId === $item['productId']) {
                            $inventoryComponent->stockLevel -= $item['quantity'];
                            echo "Order {$node->order->orderId}: Updated inventory for product {$item['productId']}. New stock: {$inventoryComponent->stockLevel}\n";
                            break;
                        }
                    }
                }
                $node->order->status = 'inventory_reserved';
            } else {
                $node->order->status = 'cancelled_no_stock';
            }
        }
    }
}