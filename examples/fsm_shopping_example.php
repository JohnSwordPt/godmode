<?php

require __DIR__ . '/../vendor/autoload.php';

use godmode\data\Blackboard;
use godmode\data\VectorBehaviorPredicate;
use godmode\data\VectorBehaviorTask;
use godmode\core\BehaviorTree;
use godmode\selector\PrioritySelector;
use godmode\selector\SequenceSelector;
use godmode\pred\AndPredicate;
use godmode\core\BehaviorTask;
use godmode\pred\EntryEqualsPred;
use godmode\task\FunctionTask;
use godmode\TaskFactory;
use godmode\pred\FunctionPredicate;

// The blackboard holds the state of our world.
$blackboard = new Blackboard();

// Initial state
$blackboard->getEntry('at_home')->store(true);
$blackboard->getEntry('at_store')->store(false);
$blackboard->getEntry('fridge_stock')->store([
    'milk' => 1,
    'cheese' => 0,
    'wine' => 0,
    'water' => 0,
]);
$blackboard->getEntry('products_to_buy')->store([]); // List of product names to buy
$blackboard->getEntry('money')->store(20); // We have $20
$blackboard->getEntry('gas')->store(10); // We have 10 units of gas
$blackboard->getEntry('distance_to_store')->store(4); // It takes 4 units of gas to drive one way

// Store inventory
$blackboard->getEntry('store_inventory')->store([
    'milk' => ['stock' => 5, 'price' => 3],
    'cheese' => ['stock' => 10, 'price' => 5],
    'wine' => ['stock' => 1, 'price' => 25],
]);

echo "INITIAL STATE: \n";
echo "At home: " . ($blackboard->getEntry('at_home')->value() ? 'Yes' : 'No') . "\n";
foreach ($blackboard->getEntry('fridge_stock')->value() as $product => $quantity) {
    echo ucfirst($product) . " units: " . $quantity . "\n";
}
echo "Money: $" . $blackboard->getEntry('money')->value() . "\n";
echo "Gas: " . $blackboard->getEntry('gas')->value() . " units\n";
echo "Store Stock:\n";
foreach ($blackboard->getEntry('store_inventory')->value() as $product => $details) {
    echo "  - " . ucfirst($product) . ": " . $details['stock'] . " units at $" . $details['price'] . " each\n";
}
echo "---------------------\n\n";


// PREDICATES (Conditions)

// Check if we are missing any required groceries and populate the 'products_to_buy' list.
$isMissingGroceries = new FunctionPredicate(function() use ($blackboard) {
    $fridge = $blackboard->getEntry('fridge_stock')->value();
    $needed = [];
    foreach ($fridge as $product => $quantity) {
        if ($quantity === 0) {
            $needed[] = $product;
        }
    }

    if (!empty($needed)) {
        $blackboard->getEntry('products_to_buy')->store($needed);
        return true;
    }
    return false;
});

// Do we have enough gas for a round trip?
$canGoShopping = new FunctionPredicate(function() use ($blackboard) {
    $gasNeeded = $blackboard->getEntry('distance_to_store')->value() * 2;
    $hasGas = $blackboard->getEntry('gas')->value() >= $gasNeeded;
    if (!$hasGas) echo ">> Not enough gas for a round trip!\n";
    return $hasGas;
});

// Are we at home, missing groceries, and able to go?
$shouldGoShopping = new AndPredicate(new VectorBehaviorPredicate([
    new EntryEqualsPred($blackboard->getEntry('at_home'), true),
    $isMissingGroceries,
    $canGoShopping
]));


// TASKS (Actions)

$tFactory = new TaskFactory(null);

$driveToStoreTask = new FunctionTask(function (float $dt) use ($blackboard) {
    $bb = $blackboard;
    $gasNeeded = $bb->getEntry('distance_to_store')->value();
    $currentGas = $bb->getEntry('gas')->value();
    if ($currentGas >= $gasNeeded) {
        echo ">> Driving to the store... (used {$gasNeeded} gas)\n";
        $bb->getEntry('gas')->store($currentGas - $gasNeeded);
        $bb->getEntry('at_home')->store(false);
        $bb->getEntry('at_store')->store(true);
        return BehaviorTask::SUCCESS;
    }
    echo ">> Not enough gas to drive to the store!\n";
    return BehaviorTask::FAIL;
});

// This task will loop through the 'products_to_buy' list and attempt to purchase each item.
// This decorator will always return SUCCESS, ensuring the main sequence continues.
$buyGroceriesTask = $tFactory->loopUntilComplete(
    // This decorator will loop until it fails (i.e., the shopping list is empty).
    $tFactory->loopUntilFail(
        $tFactory->sequence([
            // This predicate sets the 'buy' entry for the next product in the list. Fails when list is empty.
            new FunctionPredicate(function() use ($blackboard) {
                $productsToBuy = $blackboard->getEntry('products_to_buy')->value();
                if (empty($productsToBuy)) {
                    return false; // No more products to buy, fail the loop.
                }
                $product = array_shift($productsToBuy);
                $blackboard->getEntry('buy')->store($product); // Set current product to buy
                $blackboard->getEntry('products_to_buy')->store($productsToBuy); // Update the list
                return true;
            }),
            // This decorator ensures that even if buying one item fails, the outer loop continues.
            $tFactory->loopUntilComplete($tFactory->sequence([
                // Check if the store has the current product in stock.
                new FunctionPredicate(function() use ($blackboard) {
                    $product = $blackboard->getEntry('buy')->value();
                    $inventory = $blackboard->getEntry('store_inventory')->value();
                    if (!isset($inventory[$product])) {
                        echo ">> Store does not sell '{$product}'.\n";
                        return false;
                    }
                    if ($inventory[$product]['stock'] > 0) {
                        echo ">> Store has '{$product}'.\n";
                        return true;
                    }
                    echo ">> Store is out of stock for '{$product}'!\n";
                    return false;
                }),
                // Attempt to buy the product.
                new FunctionTask(function (float $dt) use ($blackboard) {
                    $bb = $blackboard;
                    $product = $bb->getEntry('buy')->value();
                    $money = $bb->getEntry('money')->value();
                    $inventory = $bb->getEntry('store_inventory')->value();
                    $price = $inventory[$product]['price'];

                    if ($money < $price) {
                        echo "   - Not enough money to buy '{$product}'! (Need \${$price})\n";
                        return BehaviorTask::FAIL;
                    }

                    // Buy the product
                    $inventory[$product]['stock']--;
                    $money -= $price;
                    $fridge = $bb->getEntry('fridge_stock')->value();
                    $fridge[$product]++;

                    $bb->getEntry('money')->store($money);
                    $bb->getEntry('store_inventory')->store($inventory);
                    $bb->getEntry('fridge_stock')->store($fridge);
                    echo "   - Bought 1 unit of '{$product}' for \${$price}.\n";
                    return BehaviorTask::SUCCESS;
                })
            ]))
        ])
    )
);

$driveHomeTask = new FunctionTask(function (float $dt) use ($blackboard) {
    $bb = $blackboard;
    $gasNeeded = $bb->getEntry('distance_to_store')->value();
    $currentGas = $bb->getEntry('gas')->value();
    if ($currentGas >= $gasNeeded) {
        echo ">> Driving back home... (used {$gasNeeded} gas)\n";
        $bb->getEntry('gas')->store($currentGas - $gasNeeded);
        $bb->getEntry('at_store')->store(false);
        $bb->getEntry('at_home')->store(true);
        return BehaviorTask::SUCCESS;
    }
    echo ">> Not enough gas to drive home! Stranded!\n";
    return BehaviorTask::FAIL;
});

$stayHomeTask = new FunctionTask(function (float $dt) use ($blackboard) {
    echo ">> No need to go shopping. Staying home.\n";
    return true;
});


// BEHAVIOR TREE CONSTRUCTION

// The shopping trip is a sequence of tasks that must be done in order.
$shoppingTripSequence = new SequenceSelector(new VectorBehaviorTask(...[
    $driveToStoreTask,
    $buyGroceriesTask,
    $driveHomeTask,
]));

// We only go on the shopping trip IF our predicate ($shouldGoShopping) is true.
$guardedShoppingTrip = $tFactory->enterIf($shouldGoShopping, $shoppingTripSequence);

// The root of our tree is a PrioritySelector. It tries each child in order until one succeeds.
// 1. Try to go shopping (will only run if the guard predicate is met).
// 2. If that fails (i.e., we don't need to shop), then just stay home.
$root = new PrioritySelector(new VectorBehaviorTask(...[
    $guardedShoppingTrip,
    $stayHomeTask,
]));

// Create and run the behavior tree
$tree = new BehaviorTree($root);

echo "Ticking the behavior tree...\n";
while ($tree->update(0.1) === BehaviorTask::RUNNING) {
    // Keep ticking until the tree is no longer in a RUNNING state.
}
echo "---------------------\n\n";

echo "FINAL STATE: \n";
echo "At home: " . ($blackboard->getEntry('at_home')->value() ? 'Yes' : 'No') . "\n";
foreach ($blackboard->getEntry('fridge_stock')->value() as $product => $quantity) {
    echo ucfirst($product) . " units: " . $quantity . "\n";
}
echo "Money: $" . $blackboard->getEntry('money')->value() . "\n";
echo "Gas: " . $blackboard->getEntry('gas')->value() . " units\n";
echo "Store Stock:\n";
foreach ($blackboard->getEntry('store_inventory')->value() as $product => $details) {
    echo "  - " . ucfirst($product) . ": " . $details['stock'] . " units at $" . $details['price'] . " each\n";
}
echo "---------------------\n";
