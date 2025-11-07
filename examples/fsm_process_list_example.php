<?php

use fsm\FSM;

require __DIR__ . '/../vendor/autoload.php';


// --- FSM States ---
const STATE_RED = 'RED';
const STATE_YELLOW = 'YELLOW';
const STATE_GREEN = 'GREEN';
const STATE_END = 'END'; // Terminal state for the simulation

// --- FSM Events (Symbols) ---
const EVENT_TIMER_TICK = 'TIMER_TICK';
const EVENT_STOP_SIMULATION = 'STOP_SIMULATION';

// --- Initial Payload ---
$payload = [
    'cycles_completed' => 0,
];

// --- FSM Definition ---
// Start in RED state
$fsm = new FSM(STATE_RED, $payload);

/**
 * addTransition($symbol, $state, $nextState, $action)
 *
 * Defines event-driven rules.
 */

// Rule 1: From RED, on TIMER_TICK, go to GREEN
$fsm->addTransition(EVENT_TIMER_TICK, STATE_RED, STATE_GREEN, function ($symbol, &$payload) {
    echo "Action: Light turns GREEN.\n";
});

// Rule 2: From GREEN, on TIMER_TICK, go to YELLOW
$fsm->addTransition(EVENT_TIMER_TICK, STATE_GREEN, STATE_YELLOW, function ($symbol, &$payload) {
    echo "Action: Light turns YELLOW.\n";
});

// Rule 3: From YELLOW, on TIMER_TICK, go to RED and complete a cycle
$fsm->addTransition(EVENT_TIMER_TICK, STATE_YELLOW, STATE_RED, function ($symbol, &$payload) {
    echo "Action: Light turns RED. Cycle completed.\n";
    $payload['cycles_completed']++;
});

// Rule 4: Any state, on STOP_SIMULATION, go to END
$fsm->addTransitionAny(STATE_RED, STATE_END, function() { /* no action */ }, EVENT_STOP_SIMULATION);
$fsm->addTransitionAny(STATE_YELLOW, STATE_END, function() { /* no action */ }, EVENT_STOP_SIMULATION);
$fsm->addTransitionAny(STATE_GREEN, STATE_END, function() { /* no action */ }, EVENT_STOP_SIMULATION);

// --- Simulation ---

function printStatus(FSM $fsm) {
    $p = $fsm->getPayload();
    echo "Status: State='{$fsm->getCurrentState()}', Cycles Completed={$p['cycles_completed']}\n";
    echo "--------------------------------------------------\n";
}

echo "--- Traffic Light Simulation (using processList) ---\n";
printStatus($fsm);

// Define a sequence of events
$events = [
    EVENT_TIMER_TICK, // RED -> GREEN
    EVENT_TIMER_TICK, // GREEN -> YELLOW
    EVENT_TIMER_TICK, // YELLOW -> RED (1st cycle)
    EVENT_TIMER_TICK, // RED -> GREEN
    EVENT_TIMER_TICK, // GREEN -> YELLOW
    EVENT_TIMER_TICK, // YELLOW -> RED (2nd cycle)
    EVENT_STOP_SIMULATION, // Stop the FSM
];

echo "Processing event list...\n";
$fsm->processList($events);

echo "\nSimulation finished.\n";
printStatus($fsm);