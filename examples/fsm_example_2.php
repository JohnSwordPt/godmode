<?php
// https://www.php.net/manual/en/class.splqueue.php
// https://pear.github.io/FSM/
// https://www.php.net/manual/en/spl.datastructures.php

require __DIR__ . '/../vendor/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', 'On');

use fsm\FSM;

$payload = [
    'milk' => 0,
    'money' => 10,
    'price' => 5,
    'gas' => 10,
    'store_open' => true,
    'stock_milk' => 99
];

echo "<pre>" . PHP_EOL;
var_dump($payload);
echo "</pre><br>" . PHP_EOL;

$drivingLogic = (new class {
    public function execute()
    {
        $args = func_get_args();
        echo "{$args[2]} > {$args[3]}<br>" . PHP_EOL;
        $payload = $args[4]->getPayload();
        if($payload['milk'] > 0 || $payload['money'] <= 0 || $payload['gas'] <= 0) {
            return 'CANCEL_DRIVING';
        }
    }
});

$fsm = (new FSM('INITIAL_STATE', $payload))
// ->addTransitionAny('INITIAL_STATE', 'DRIVING_TO_BUY_MILK', function($symbol, &$payload) {
//     $args = func_get_args();
//     echo "{$args[2]} > {$args[3]}<br>";
//     if($payload['milk'] > 0 || $payload['money'] <= 0 || $payload['gas'] <= 0) {
//         return 'CANCEL_DRIVING';
//     }
// })
->addTransitionAny('INITIAL_STATE', 'DRIVING_TO_BUY_MILK', $drivingLogic)
->addTransitionAny('CANCEL_DRIVING', 'END', function() {
    $args = func_get_args();
	echo "{$args[2]} > {$args[3]}<br>" . PHP_EOL;
	// $payload = $args[1];
})->addTransitionAny('DRIVING_TO_BUY_MILK', 'PICKING_THE_MILK', function($symbol, &$payload) {
    $args = func_get_args();
    echo "{$args[2]} > {$args[3]}<br>" . PHP_EOL;
    $payload['gas'] --;
    if($payload['store_open'] == false || $payload['stock_milk'] <= 0) {
        return 'UNABLE_TO_PURCHASE';
    }
})->addTransitionAny('PICKING_THE_MILK', 'PAYING_FOR_THE_MILK', function($symbol, &$payload) {
    $args = func_get_args();
    echo "{$args[2]} > {$args[3]}<br>" . PHP_EOL;
    if($payload['money'] < $payload['price']) {
        return 'UNABLE_TO_PURCHASE';
    } else {
        $payload['money'] -= $payload['price'];
        $payload['milk'] = 1;
        $payload['stock_milk'] --;
    }
})->addTransitionAny('PAYING_FOR_THE_MILK', 'DRIVE_BACK_HOME', function() {
    $args = func_get_args();
    echo "{$args[2]} > {$args[3]}<br>" . PHP_EOL;
})->addTransitionAny('UNABLE_TO_PURCHASE', 'DRIVE_BACK_HOME', function() {
    $args = func_get_args();
    echo "{$args[2]} > {$args[3]}<br>" . PHP_EOL;
})->addTransitionAny('DRIVE_BACK_HOME', 'END', function($symbol, &$payload) {
	$args = func_get_args();
	echo "{$args[2]} > {$args[3]}<br>" . PHP_EOL;
    $payload['gas'] --;
})->addTransitionAny('END', '', function($symbol, &$payload) {
	$args = func_get_args();
	echo "{$args[2]} > {$args[3]}<br>" . PHP_EOL;
    // getting the payload from the FSM
    $payload = $args[4]->getPayload();
})
->processAll();

echo "<pre>" . PHP_EOL;
var_dump($fsm->getPayload());
echo "</pre><br>" . PHP_EOL;

$stack = [];
$fsm = new FSM('START', $stack);
$fsm->addTransition('FIRST', 'START', 'MIDDLE', function() {
    echo 'FIRST Transition<br>';
});
$fsm->addTransition('SECOND', 'MIDDLE', 'END', function() {
    echo 'SECOND Transition<br>';
});
$fsm->setDefaultTransition('START', function($symbol) {
    echo "This symbol does not compute: $symbol<br>";
});
// $fsm->process('FIRST');
// $fsm->process('SECOND');
$fsm->processList(['FIRST', 'SECOND']);
