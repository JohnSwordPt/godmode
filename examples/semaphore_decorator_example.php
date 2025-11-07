<?php

require_once __DIR__ . '/../vendor/autoload.php';

use godmode\core\BehaviorTree;
use godmode\core\Semaphore;
use godmode\decorator\SemaphoreDecorator;
use godmode\data\VectorBehaviorTask;
use godmode\selector\ParallelSelector;
use godmode\task\FunctionTask;
use godmode\core\BehaviorTask;

// A semaphore with a capacity of 1 will act as a mutex, allowing only one task to run.
$semaphore = new Semaphore('my_mutex', 1);

// Task 1
$task1 = new FunctionTask(function () {
    echo "Task 1 is running..." . PHP_EOL;
    return BehaviorTask::SUCCESS;
});

// Task 2
$task2 = new FunctionTask(function () {
    echo "Task 2 is running... (This should not be printed)" . PHP_EOL;
    return BehaviorTask::SUCCESS;
});

// Decorate both tasks with the *same* semaphore.
$decoratedTask1 = new SemaphoreDecorator($semaphore, $task1);
$decoratedTask2 = new SemaphoreDecorator($semaphore, $task2);

// A ParallelSelector attempts to run all its children in the same update tick.
$parallel = new ParallelSelector(ParallelSelector::ANY_SUCCESS, new VectorBehaviorTask($decoratedTask1, $decoratedTask2));

$tree = new BehaviorTree($parallel);

echo "Running tree...\n";
$tree->update(0.0);

echo "\nTree update complete.\n";
echo "Only the first task should have run, as the semaphore blocked the second.\n";

?>