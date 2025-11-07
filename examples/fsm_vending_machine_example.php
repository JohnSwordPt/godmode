<?php
require __DIR__ . '/../vendor/autoload.php';

use fsm\FSM;

// --- FSM States ---
const STATE_LOCKED = 'LOCKED';       // Waiting for a coin
const STATE_UNLOCKED = 'UNLOCKED';   // Ready to dispense
const STATE_END = 'END';             // Terminal state

// --- FSM Events (Symbols) ---
const EVENT_INSERT_COIN = 'INSERT_COIN';
const EVENT_PUSH_BUTTON = 'PUSH_BUTTON';

// --- Initial Payload ---
$payload = [
    'coins' => 0,
    'drinks_dispensed' => 0,
];

// --- FSM Definition ---
$fsm = new FSM(STATE_LOCKED, $payload);

/**
 * addTransition($symbol, $state, $nextState, $action)
 *
 * Defines event-driven rules.
 */

// Rule: If 'LOCKED' and we get a 'COIN', go to 'UNLOCKED'.
$fsm->addTransition(EVENT_INSERT_COIN, STATE_LOCKED, STATE_UNLOCKED, function ($symbol, &$payload) {
    echo "Action: Coin accepted. Machine is unlocked.\n";
    $payload['coins']++;
});

// Rule: If 'LOCKED' and we get a 'PUSH', stay 'LOCKED'.
$fsm->addTransition(EVENT_PUSH_BUTTON, STATE_LOCKED, STATE_LOCKED, function () {
    echo "Action: *BEEP* Please insert a coin first.\n";
});

// Rule: If 'UNLOCKED' and we get a 'PUSH', dispense a drink and go back to 'LOCKED'.
$fsm->addTransition(EVENT_PUSH_BUTTON, STATE_UNLOCKED, STATE_LOCKED, function ($symbol, &$payload) {
    echo "Action: *CLUNK* Dispensing drink. Machine is now locked.\n";
    $payload['drinks_dispensed']++;
});

// Rule: If 'UNLOCKED' and we get another 'COIN', stay 'UNLOCKED' and return the coin.
$fsm->addTransition(EVENT_INSERT_COIN, STATE_UNLOCKED, STATE_UNLOCKED, function () {
    echo "Action: Machine already unlocked. Returning extra coin.\n";
});


// --- Simulation ---

function printStatus(FSM $fsm) {
    $p = $fsm->getPayload();
    echo "Status: State='{$fsm->getCurrentState()}', Coins={$p['coins']}, Drinks={$p['drinks_dispensed']}\n";
    echo "--------------------------------------------------\n";
}

echo "--- Vending Machine Simulation ---\n";
printStatus($fsm);

echo "Event: PUSH_BUTTON\n";
$fsm->process(EVENT_PUSH_BUTTON);
printStatus($fsm);

echo "Event: INSERT_COIN\n";
$fsm->process(EVENT_INSERT_COIN);
printStatus($fsm);

echo "Event: INSERT_COIN (extra coin)\n";
$fsm->process(EVENT_INSERT_COIN);
printStatus($fsm);

echo "Event: PUSH_BUTTON\n";
$fsm->process(EVENT_PUSH_BUTTON);
printStatus($fsm);

echo "Event: INSERT_COIN\n";
$fsm->process(EVENT_INSERT_COIN);
printStatus($fsm);

echo "Event: PUSH_BUTTON\n";
$fsm->process(EVENT_PUSH_BUTTON);
printStatus($fsm);