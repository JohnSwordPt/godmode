<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

require __DIR__ . '/../vendor/autoload.php';

use godmode\core\BehaviorTask;
use godmode\decorator\LoopingDecorator;
use godmode\task\FunctionTask;

// 1. --- Setup the counter ---
$counter = 0;

// 2. --- Define the task ---
$task = new FunctionTask(function() use (&$counter) {
    $counter++;
    echo "Executing task for the {$counter} time.\n";
    if ($counter >= 3) {
        echo "Task finished successfully.\n";
        return BehaviorTask::SUCCESS;
    }
    return BehaviorTask::RUNNING;
});

// 3. --- Create the LoopingDecorator that breaks on success ---
$loopingDecoratorOnSuccess = new LoopingDecorator(LoopingDecorator::BREAK_ON_SUCCESS, 0, $task);

// 4. --- Run the simulation for BREAK_ON_SUCCESS ---
echo "### LoopingDecorator Simulation Start (BREAK_ON_SUCCESS) ###\n\n";

while ($loopingDecoratorOnSuccess->update(0.1) == BehaviorTask::RUNNING) {
    // Keep updating until the decorator breaks
}

echo "\n### LoopingDecorator Simulation End (BREAK_ON_SUCCESS) ###\n\n";

// 5. --- Reset the counter for the next example ---
$counter = 0;

// 6. --- Create the LoopingDecorator that loops for a fixed number of times ---
$task2 = new FunctionTask(function() use (&$counter) {
    $counter++;
    echo "Executing task for the {$counter} time.\n";
    if ($counter >= 3) {
        echo "Task finished successfully.\n";
        return BehaviorTask::SUCCESS;
    }
    return BehaviorTask::RUNNING;
});
$loopingDecoratorFixedTimes = new LoopingDecorator(LoopingDecorator::BREAK_NEVER, 4, $task2);

// 7. --- Run the simulation for fixed number of loops ---
echo "### LoopingDecorator Simulation Start (Fixed Number of Loops) ###\n\n";

while ($loopingDecoratorFixedTimes->update(0.1) == BehaviorTask::RUNNING) {
    // Keep updating until the decorator breaks
}

echo "\n### LoopingDecorator Simulation End (Fixed Number of Loops) ###\n";
