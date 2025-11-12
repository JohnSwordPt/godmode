<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use ECS\Engine;
use ECS\Entity;
use BusinessLogicECS\Components\OrderComponent;
use BusinessLogicECS\Components\PriceComponent;
use BusinessLogicECS\Components\InventoryComponent;
use BusinessLogicECS\Systems\OrderCalculationSystem;
use BusinessLogicECS\Systems\InventoryManagementSystem;
use BusinessLogicECS\Systems\OrderCompletionSystem;

echo "Starting ECS Business Logic Example (Order Processing)\n";

$engine = new Engine();

// Add systems to the engine
$engine->AddSystem(new OrderCalculationSystem(), 1);
$engine->AddSystem(new InventoryManagementSystem(), 2);
$engine->AddSystem(new OrderCompletionSystem(), 3);

// --- Create Entities ---

// Product Inventory Entities
$productAInventory = (new Entity('ProductA_Inventory'))
    ->add(new InventoryComponent('P1', 10)); // 10 units of Product P1
$engine->AddEntity($productAInventory);

$productBInventory = (new Entity('ProductB_Inventory'))
    ->add(new InventoryComponent('P2', 5)); // 5 units of Product P2
$engine->AddEntity($productBInventory);

// Order 1: Should complete successfully
$order1 = (new Entity('Order_001'))
    ->add(new OrderComponent('ORD-001', 'Alice', [
        ['productId' => 'P1', 'quantity' => 2],
        ['productId' => 'P2', 'quantity' => 1],
    ]))
    ->add(new PriceComponent(10.00, 3)); // Unit price for P1 is 10, P2 is 20. Total quantity 3.
                                        // This PriceComponent is simplified for the example.
                                        // In a real app, prices would be per item or looked up.
                                        // Here, 10.00 is a placeholder unit price, and 3 is total quantity.
                                        // The OrderCalculationSystem will use the items array to calculate total.
$engine->AddEntity($order1);

// Order 2: Should be cancelled due to insufficient stock for P2
$order2 = (new Entity('Order_002'))
    ->add(new OrderComponent('ORD-002', 'Bob', [
        ['productId' => 'P1', 'quantity' => 1],
        ['productId' => 'P2', 'quantity' => 10], // Requesting 10, but only 5 available
    ]))
    ->add(new PriceComponent(15.00, 11)); // Placeholder price
$engine->AddEntity($order2);

echo "\n--- Initial State ---\n";
foreach ($engine->Entities() as $entity) {
    echo "Entity: {$entity->Name}\n";
    foreach ($entity->getAll() as $component) {
        if ($component instanceof OrderComponent) {
            echo "  OrderComponent: ID={$component->orderId}, Customer={$component->customerName}, Status={$component->status}, Items=".json_encode($component->items)."\n";
        } elseif ($component instanceof PriceComponent) {
            echo "  PriceComponent: UnitPrice={$component->unitPrice}, Quantity={$component->quantity}, TotalPrice={$component->totalPrice}\n";
        } elseif ($component instanceof InventoryComponent) {
            echo "  InventoryComponent: ProductID={$component->productId}, StockLevel={$component->stockLevel}\n";
        }
    }
}

echo "\n--- Running Engine Update (Processing Orders) ---\n";
// Run the engine update loop multiple times to allow systems to process
// In a real application, this would be part of a continuous loop or triggered by events.
for ($i = 0; $i < 5; $i++) {
    echo "\n--- Engine Update Cycle " . ($i + 1) . " ---\n";
    $engine->Update(0.1); // Pass a small delta time
}

echo "\n--- Final State ---\n";
foreach ($engine->Entities() as $entity) {
    echo "Entity: {$entity->Name}\n";
    foreach ($entity->getAll() as $component) {
        if ($component instanceof OrderComponent) {
            echo "  OrderComponent: ID={$component->orderId}, Customer={$component->customerName}, Status={$component->status}, Items=".json_encode($component->items)."\n";
        } elseif ($component instanceof PriceComponent) {
            echo "  PriceComponent: UnitPrice={$component->unitPrice}, Quantity={$component->quantity}, TotalPrice={$component->totalPrice}\n";
        } elseif ($component instanceof InventoryComponent) {
            echo "  InventoryComponent: ProductID={$component->productId}, StockLevel={$component->stockLevel}\n";
        }
    }
}

echo "\nECS Business Logic Example Finished.\n";
