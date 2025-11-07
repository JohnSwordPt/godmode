<?php
require __DIR__ . '/../vendor/autoload.php';

use fsm\FSM;

// --- FSM States ---
const STATE_START = 'START';
const STATE_COUNTING = 'COUNTING';
const STATE_FINISHED = 'FINISHED';

// --- Initial Payload ---
$payload = [
    'counter' => 5, // Start counting down from 5
];

// --- FSM Definition ---
$fsm = new FSM(STATE_START, $payload);

// --- Transitions and Actions ---

// START -> COUNTING: Initialize the process
$fsm->addTransitionAny(STATE_START, STATE_COUNTING, function($symbol, &$payload, $currentState, $nextState, $fsmInstance) {
    echo ">> FSM starting countdown from {$payload['counter']}.\n";
    return STATE_COUNTING;
});

// COUNTING -> COUNTING (loop) or COUNTING -> FINISHED (condition met)
$fsm->addTransitionAny(STATE_COUNTING, STATE_FINISHED, function($symbol, &$payload, $currentState, $nextState, $fsmInstance) {
    if ($payload['counter'] > 0) {
        $payload['counter']--;
        echo ">> Counting down: {$payload['counter']}\n";
        // To loop into itself, return the current state.
        // This requires a manual loop calling process(), not processAll().
        return STATE_COUNTING;
    }
    // Condition met, counter is 0. Transition out.
    // We explicitly check for 0 here to ensure the message only prints once
    // when the condition is truly met, not on every subsequent check if
    // the counter was already 0.
    // This also handles the case where initial counter was 0.
    if ($payload['counter'] === 0) {
        echo ">> Counter reached zero. Condition met!\n";
        return STATE_FINISHED; // Transition to the next state
    }
});

// FINISHED -> null: Terminate the FSM
$fsm->addTransitionAny(STATE_FINISHED, null, function($symbol, &$payload, $currentState, $nextState, $fsmInstance) {
    echo ">> FSM has finished execution. Final state: {$currentState}\n";
    return null; // Returning null stops process
});

// --- Process the FSM ---
echo "--- FSM Loop Until Condition Example ---\n";
echo "Initial Counter: {$fsm->getPayload()['counter']}\n";
echo "----------------------------------------\n";

echo "Processing FSM...\n";
// We cannot use processAll() if a state loops into itself by returning its own state name.
// We must use a manual loop calling process() with a dummy symbol.
while ($fsm->getCurrentState() !== null) {
    $fsm->process('TICK'); // Use a dummy symbol or null, as addTransitionAny doesn't care about it.
}

echo "----------------------------------------\n";
echo "Simulation finished.\n";
echo "Final Counter: {$fsm->getPayload()['counter']}\n";