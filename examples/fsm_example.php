<?php

require __DIR__ . '/../vendor/autoload.php';

use fsm\FSM;

// Action functions
$thankYouAction = function ($symbol, &$payload, $currentState, $nextState, $fsm) {
    echo "Action: Thank you for the coin! Unlocking the turnstile." . PHP_EOL;
    $payload['coins']++;
};

$alarmAction = function ($symbol, &$payload, $currentState, $nextState, $fsm) {
    echo "Action: ALARM! You can't push a locked turnstile." . PHP_EOL;
};

$welcomeAction = function ($symbol, &$payload, $currentState, $nextState, $fsm) {
    echo "Action: Welcome! Locking the turnstile behind you." . PHP_EOL;
};

// Initialize the payload
$payload = ['coins' => 0];

// Create a new FSM with the initial state 'Locked' and the payload
$fsm = new FSM('Locked', $payload);

// Define the transitions
$fsm->addTransition('Coin', 'Locked', 'Unlocked', $thankYouAction);
$fsm->addTransition('Push', 'Locked', 'Locked', $alarmAction);
$fsm->addTransition('Coin', 'Unlocked', 'Unlocked', $thankYouAction);
$fsm->addTransition('Push', 'Unlocked', 'Locked', $welcomeAction);

// --- Simulation ---

echo "Initial State: " . $fsm->getCurrentState() . PHP_EOL;
echo "-------------------------" . PHP_EOL;

echo "Input: Push" . PHP_EOL;
$fsm->process('Push');
echo "Current State: " . $fsm->getCurrentState() . PHP_EOL;
echo "-------------------------" . PHP_EOL;

echo "Input: Coin" . PHP_EOL;
$fsm->process('Coin');
echo "Current State: " . $fsm->getCurrentState() . PHP_EOL;
echo "-------------------------" . PHP_EOL;

echo "Input: Coin" . PHP_EOL;
$fsm->process('Coin');
echo "Current State: " . $fsm->getCurrentState() . PHP_EOL;
echo "-------------------------" . PHP_EOL;

echo "Input: Push" . PHP_EOL;
$fsm->process('Push');
echo "Current State: " . $fsm->getCurrentState() . PHP_EOL;
echo "-------------------------" . PHP_EOL;

echo "Input: Push" . PHP_EOL;
$fsm->process('Push');
echo "Current State: " . $fsm->getCurrentState() . PHP_EOL;
echo "-------------------------" . PHP_EOL;

$finalPayload = $fsm->getPayload();
echo "Final coin count: " . $finalPayload['coins'] . PHP_EOL;

?>
