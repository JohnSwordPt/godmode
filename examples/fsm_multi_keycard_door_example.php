<?php
require __DIR__ . '/../vendor/autoload.php';

use fsm\FSM;

// --- FSM States ---
const STATE_LOCKED = 'LOCKED';
const STATE_UNLOCKED = 'UNLOCKED';
const STATE_ALARM = 'ALARM';
const STATE_END = 'END';

// --- FSM Events (Symbols) ---
const EVENT_KEYCARD_ALPHA = 'KEYCARD_ALPHA';
const EVENT_KEYCARD_BETA = 'KEYCARD_BETA';
const EVENT_KEYCARD_GAMMA = 'KEYCARD_GAMMA';
const EVENT_TAMPER = 'TAMPER';
const EVENT_CLOSE_DOOR = 'CLOSE_DOOR';

// --- Initial Payload ---
$payload = [
    'access_attempts' => 0,
    'alarm_triggered' => false,
];

// --- FSM Definition ---
$fsm = new FSM(STATE_LOCKED, $payload);

/**
 * addTransitions($symbols, $state, $nextState, $action)
 *
 * Defines rules for multiple symbols leading to the same outcome.
 */

// Rule 1: If 'LOCKED' and any valid keycard is used, go to 'UNLOCKED'.
$validKeycards = [EVENT_KEYCARD_ALPHA, EVENT_KEYCARD_BETA, EVENT_KEYCARD_GAMMA];
$fsm->addTransitions($validKeycards, STATE_LOCKED, STATE_UNLOCKED, function ($symbol, &$payload) {
    echo "Action: Keycard '{$symbol}' accepted. Door is now unlocked.\n";
    $payload['access_attempts']++;
});

// Rule 2: If 'LOCKED' and 'TAMPER' event occurs, go to 'ALARM'.
$fsm->addTransition(EVENT_TAMPER, STATE_LOCKED, STATE_ALARM, function ($symbol, &$payload) {
    echo "Action: WARNING! Tampering detected! Alarm triggered!\n";
    $payload['alarm_triggered'] = true;
});

// Rule 3: If 'UNLOCKED' and 'CLOSE_DOOR' event occurs, go back to 'LOCKED'.
$fsm->addTransition(EVENT_CLOSE_DOOR, STATE_UNLOCKED, STATE_LOCKED, function () {
    echo "Action: Door closed. Re-locking.\n";
});

// Rule 4: From 'ALARM' state, any event leads to END for simplicity.
$fsm->addTransitionAny(STATE_ALARM, STATE_END, function () {
    echo "Action: Alarm active. System shutting down for security.\n";
});

// --- Simulation ---

function printStatus(FSM $fsm) {
    $p = $fsm->getPayload();
    echo "Status: State='{$fsm->getCurrentState()}', Attempts={$p['access_attempts']}, Alarm=" . ($p['alarm_triggered'] ? 'YES' : 'NO') . "\n";
    echo "--------------------------------------------------\n";
}

echo "--- Multi-Keycard Door Simulation ---\n";
printStatus($fsm);

echo "Event: Pushing an unknown button (no transition defined for this in LOCKED state for this symbol)\n";
$fsm->process('UNKNOWN_BUTTON');
printStatus($fsm);

echo "Event: " . EVENT_KEYCARD_BETA . "\n";
$fsm->process(EVENT_KEYCARD_BETA);
printStatus($fsm);

echo "Event: " . EVENT_KEYCARD_ALPHA . " (while UNLOCKED)\n";
$fsm->process(EVENT_KEYCARD_ALPHA); // No specific transition for this in UNLOCKED state, so state remains UNLOCKED
printStatus($fsm);

echo "Event: " . EVENT_CLOSE_DOOR . "\n";
$fsm->process(EVENT_CLOSE_DOOR);
printStatus($fsm);

echo "Event: " . EVENT_TAMPER . "\n";
$fsm->process(EVENT_TAMPER);
printStatus($fsm);

echo "Event: Any event after alarm (e.g., " . EVENT_KEYCARD_ALPHA . ")\n";
$fsm->process(EVENT_KEYCARD_ALPHA);
printStatus($fsm);