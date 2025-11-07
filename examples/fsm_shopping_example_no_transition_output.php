<?php
require __DIR__ . '/../vendor/autoload.php';

// We'll use the FSM class instead of the Behavior Tree library for this example
use fsm\FSM;

// Initial payload (replaces Blackboard)
$payload = [
    'at_home' => true,
    'at_store' => false,
    'fridge_stock' => [
        'milk' => 0,
        'cheese' => 0,
        'wine' => 0,
        'water' => 0,
    ],
    'products_to_buy' => [], // This will be populated by the FSM logic
    'money' => 20,
    'gas' => 10,
    'distance_to_store' => 4,
    'store_inventory' => [
        'milk' => ['stock' => 5, 'price' => 3],
        'cheese' => ['stock' => 10, 'price' => 25],
        'wine' => ['stock' => 1, 'price' => 5],
    ],
    'current_product_to_buy' => null, // To hold the product being processed during buying
];

// --- Helper function for printing state ---
function printState(array $payload, string $title) {
    echo "{$title}: \n";
    echo "At home: " . ($payload['at_home'] ? 'Yes' : 'No') . "\n";
    foreach ($payload['fridge_stock'] as $product => $quantity) {
        echo ucfirst($product) . " units: " . $quantity . "\n";
    }
    echo "Money: $" . $payload['money'] . "\n";
    echo "Gas: " . $payload['gas'] . " units\n";
    echo "Store Stock:\n";
    foreach ($payload['store_inventory'] as $product => $details) {
        echo "  - " . ucfirst($product) . ": " . $details['stock'] . " units at $" . $details['price'] . " each\n";
    }
    echo "---------------------\n\n";
}

printState($payload, "INITIAL STATE");

// --- FSM States ---
const STATE_INITIAL = 'INITIAL';
const STATE_CHECK_CONDITIONS = 'CHECK_CONDITIONS';
const STATE_DRIVING_TO_STORE = 'DRIVING_TO_STORE';
const STATE_AT_STORE_BUYING = 'AT_STORE_BUYING'; // Kept for clarity, but we'll use the states below
const STATE_AT_STORE_BUYING_START = 'AT_STORE_BUYING_START';
const STATE_PROCESS_SINGLE_PRODUCT = 'PROCESS_SINGLE_PRODUCT';
const STATE_CHECK_MORE_PRODUCTS = 'CHECK_MORE_PRODUCTS';
const STATE_DRIVING_HOME = 'DRIVING_HOME';
const STATE_STRANDED_AT_STORE = 'STRANDED_AT_STORE';
const STATE_STAY_HOME = 'STAY_HOME';
const STATE_END = 'END';

$fsm = new FSM(STATE_INITIAL, $payload);

// --- Transitions and Actions ---

// INITIAL -> CHECK_CONDITIONS
$fsm->addTransitionAny(STATE_INITIAL, STATE_CHECK_CONDITIONS, function($symbol, &$payload, $currentState, $nextState, $fsmInstance) {
    // echo "{$currentState} > {$nextState}\n"; // Removed transition output
});

// CHECK_CONDITIONS -> DRIVING_TO_STORE or STAY_HOME
$fsm->addTransitionAny(STATE_CHECK_CONDITIONS, STATE_DRIVING_TO_STORE, function($symbol, &$payload, $currentState, $nextState, $fsmInstance) {
    // echo "{$currentState} > {$nextState}\n"; // Removed transition output

    // Check if missing groceries and populate products_to_buy
    $fridge = $payload['fridge_stock'];
    $needed = [];
    foreach ($fridge as $product => $quantity) {
        if ($quantity === 0) {
            $needed[] = $product;
        }
    }
    $payload['products_to_buy'] = $needed;

    $isMissingGroceries = !empty($needed);

    // Check gas for round trip
    $gasNeededForRoundTrip = $payload['distance_to_store'] * 2;
    $hasEnoughGas = $payload['gas'] >= $gasNeededForRoundTrip;

    // Check if at home
    $isAtHome = $payload['at_home'];

    if ($isAtHome && $isMissingGroceries && $hasEnoughGas) {
        return STATE_DRIVING_TO_STORE;
    } else {
        if (!$isAtHome) {
            echo ">> Not at home, cannot start shopping trip.\n";
        }
        if (!$isMissingGroceries) {
            echo ">> No groceries needed.\n";
        }
        if (!$hasEnoughGas) {
            echo ">> Not enough gas for a round trip! (Needed {$gasNeededForRoundTrip}, Have {$payload['gas']})\n";
        }
        return STATE_STAY_HOME;
    }
});

// DRIVING_TO_STORE -> AT_STORE_BUYING_START or STAY_HOME (if not enough gas to even get there)
$fsm->addTransitionAny(STATE_DRIVING_TO_STORE, STATE_AT_STORE_BUYING_START, function($symbol, &$payload, $currentState, $nextState, $fsmInstance) {
    // echo "{$currentState} > {$nextState}\n"; // Removed transition output
    $gasNeeded = $payload['distance_to_store'];
    $currentGas = $payload['gas'];

    if ($currentGas >= $gasNeeded) {
        echo ">> Driving to the store... (used {$gasNeeded} gas)\n";
        $payload['gas'] -= $gasNeeded;
        $payload['at_home'] = false;
        $payload['at_store'] = true;

        return STATE_AT_STORE_BUYING_START; // After arriving, proceed to the buying loop
    } else {
        echo ">> Not enough gas to drive to the store! Stranded at home.\n";
        return STATE_STAY_HOME; // Cannot even start the trip
    }
});

// AT_STORE_BUYING_START -> PROCESS_SINGLE_PRODUCT or DRIVING_HOME
$fsm->addTransitionAny(STATE_AT_STORE_BUYING_START, STATE_PROCESS_SINGLE_PRODUCT, function($symbol, &$payload, $currentState, $nextState, $fsmInstance) {
    // This state just checks if there's anything to buy and dispatches
    if (!empty($payload['products_to_buy'])) {
        echo ">> Starting to process shopping list.\n";
        return STATE_PROCESS_SINGLE_PRODUCT;
    } else {
        echo ">> Shopping list is empty. Proceeding to drive home.\n";
        return STATE_DRIVING_HOME;
    }
});

// PROCESS_SINGLE_PRODUCT -> CHECK_MORE_PRODUCTS
$fsm->addTransitionAny(STATE_PROCESS_SINGLE_PRODUCT, STATE_CHECK_MORE_PRODUCTS, function($symbol, &$payload, $currentState, $nextState, $fsmInstance) {
    // This is where the actual buying logic for one product happens
    $product = array_shift($payload['products_to_buy']);
    $payload['current_product_to_buy'] = $product;

    $inventory = $payload['store_inventory'];
    $money = $payload['money'];

    if (!isset($inventory[$product])) {
        echo ">> Store does not sell '{$product}'.\n";
    } elseif ($inventory[$product]['stock'] <= 0) {
        echo ">> Store is out of stock for '{$product}'!\n";
    } elseif ($money < $inventory[$product]['price']) {
        echo "   - Not enough money to buy '{$product}'! (Need $" . $inventory[$product]['price'] . ")\n";
    } else {
        // Successfully buy the product
        $price = $inventory[$product]['price'];
        $inventory[$product]['stock']--;
        $money -= $price;
        $payload['money'] = $money;
        $payload['store_inventory'] = $inventory;
        $payload['fridge_stock'][$product]++;
        echo "   - Bought 1 unit of '{$product}' for $" . $price . ".\n";
    }

    // Always transition to CHECK_MORE_PRODUCTS after processing one item
    return STATE_CHECK_MORE_PRODUCTS;
});

// CHECK_MORE_PRODUCTS -> PROCESS_SINGLE_PRODUCT (loop) or DRIVING_HOME (when done)
$fsm->addTransitionAny(STATE_CHECK_MORE_PRODUCTS, STATE_PROCESS_SINGLE_PRODUCT, function($symbol, &$payload, $currentState, $nextState, $fsmInstance) {
    // Decide if there are more products to buy
    if (!empty($payload['products_to_buy'])) {
        echo ">> More products to buy. Processing next.\n";
        return STATE_PROCESS_SINGLE_PRODUCT; // Loop back to process the next item
    } else {
        echo ">> Finished buying all available products. Proceeding to drive home.\n";
        return STATE_DRIVING_HOME;
    }
});

// DRIVING_HOME -> END or STRANDED_AT_STORE (if not enough gas to get home)
$fsm->addTransitionAny(STATE_DRIVING_HOME, STATE_END, function($symbol, &$payload, $currentState, $nextState, $fsmInstance) {
    // echo "{$currentState} > {$nextState}\n"; // Removed transition output
    $gasNeeded = $payload['distance_to_store'];
    $currentGas = $payload['gas'];

    if ($currentGas >= $gasNeeded) {
        echo ">> Driving back home... (used {$gasNeeded} gas)\n";
        $payload['gas'] -= $gasNeeded;
        $payload['at_store'] = false;
        $payload['at_home'] = true;
        return STATE_END;
    } else {
        echo ">> Not enough gas to drive home! Stranded!\n";
        return STATE_STRANDED_AT_STORE;
    }
});

// STRANDED_AT_STORE -> END
$fsm->addTransitionAny(STATE_STRANDED_AT_STORE, STATE_END, function($symbol, &$payload, $currentState, $nextState, $fsmInstance) {
    // echo "{$currentState} > {$nextState}\n"; // Removed transition output
    echo ">> Agent is stranded at the store or on the way home.\n";
    return STATE_END;
});

// STAY_HOME -> END
$fsm->addTransitionAny(STATE_STAY_HOME, STATE_END, function($symbol, &$payload, $currentState, $nextState, $fsmInstance) {
    // echo "{$currentState} > {$nextState}\n"; // Removed transition output
    echo ">> Agent decided to stay home.\n";
    return STATE_END;
});

// END state action to terminate the FSM
$fsm->addTransitionAny(STATE_END, null, function($symbol, &$payload, $currentState, $nextState, $fsmInstance) {
    // echo "{$currentState} > {$nextState}\n"; // Removed transition output
    echo ">> Shopping trip concluded.\n";
    return null; // This will stop processAll()
});

// --- Process the FSM ---
echo "Processing FSM...\n";

$fsm->processAll();

printState($fsm->getPayload(), "FINAL STATE");