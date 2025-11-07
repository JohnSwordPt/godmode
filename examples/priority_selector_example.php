<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

require __DIR__ . '/../vendor/autoload.php';

use godmode\core\BehaviorTask;
use godmode\data\Blackboard;
use godmode\data\VectorBehaviorTask;
use godmode\selector\PrioritySelector;
use godmode\task\FunctionTask;

// 1. --- Setup the state using a Blackboard ---
$blackboard = new Blackboard();
$blackboard->getEntry('emergency')->store(false);
$blackboard->getEntry('important')->store(false);

// 2. --- Define the tasks ---

// Highest priority task
$emergencyTask = new FunctionTask(function() use ($blackboard) {
    if ($blackboard->getEntry('emergency')->value()) {
        echo "[EMERGENCY] Dealing with emergency!\n";
        return BehaviorTask::SUCCESS;
    }
    return BehaviorTask::FAIL;
});

// Medium priority task
$importantTask = new FunctionTask(function() use ($blackboard) {
    if ($blackboard->getEntry('important')->value()) {
        echo "[IMPORTANT] Doing important work!\n";
        return BehaviorTask::SUCCESS;
    }
    return BehaviorTask::FAIL;
});

// Lowest priority task
$normalTask = new FunctionTask(function() {
    echo "[NORMAL] Doing normal work.\n";
    return BehaviorTask::SUCCESS;
});

// 3. --- Create the PrioritySelector ---
$tasks = new VectorBehaviorTask();
$tasks->push($emergencyTask);
$tasks->push($importantTask);
$tasks->push($normalTask);

$prioritySelector = new PrioritySelector($tasks);

// 4. --- Run the simulation ---

echo "### PrioritySelector Simulation Start ###\n\n";

// --- Scenario 1: No flags set ---
echo "--- Scenario 1: No flags set. Expect NORMAL task. ---\n";
$prioritySelector->update(0.1);
echo "\n";

// --- Scenario 2: 'important' flag is set ---
echo "--- Scenario 2: 'important' flag is set. Expect IMPORTANT task. ---\n";
$blackboard->getEntry('important')->store(true);
$prioritySelector->update(0.1);
echo "\n";

// --- Scenario 3: 'emergency' flag is set ---
echo "--- Scenario 3: 'emergency' flag is set. Expect EMERGENCY task. ---\n";
$blackboard->getEntry('emergency')->store(true);
$prioritySelector->update(0.1);
echo "\n";

// --- Scenario 4: Both flags are set ---
echo "--- Scenario 4: Both flags are set. Expect EMERGENCY task (highest priority). ---\n";
$prioritySelector->update(0.1);
echo "\n";

// --- Scenario 5: 'emergency' flag is unset ---
echo "--- Scenario 5: 'emergency' flag is unset. Expect IMPORTANT task. ---\n";
$blackboard->getEntry('emergency')->store(false);
$prioritySelector->update(0.1);
echo "\n";

// --- Scenario 6: All flags are unset ---
echo "--- Scenario 6: All flags are unset. Expect NORMAL task. ---\n";
$blackboard->getEntry('important')->store(false);
$prioritySelector->update(0.1);
echo "\n";

echo "### PrioritySelector Simulation End ###\n";