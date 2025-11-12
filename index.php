<?php
// https://www.php.net/manual/en/class.splqueue.php
// https://pear.github.io/FSM/
// https://www.php.net/manual/en/spl.datastructures.php
error_reporting(E_ALL);
ini_set('display_errors', 'On');

use godmode\core\BehaviorTask;
use godmode\core\BehaviorTree;
use godmode\data\Blackboard;
use godmode\decorator\LoopingDecorator;
use godmode\selector\IterateSelector;
use godmode\selector\ParallelSelector;
use godmode\selector\SequenceSelector;
use godmode\TaskFactory;

require 'vendor/autoload.php';
require 'tasks.php';

$bb = new Blackboard();
$bb->getEntry('counter')->store(10);
$bb->getEntry('fridge_stock')->store([
    'milk' => 0,
    'butter' => 0,
    'cookies' => 0,
]);
$bb->getEntry('money')->store(30);
$bb->getEntry('gas')->store(20);
$bb->getEntry('distance')->store(5);
$bb->getEntry('store_open')->store(true);
$bb->getEntry('store_stock')->store([
    'milk' => [
        'stock' => 1,
        'price' => 1
    ],
    'butter' => [
        'stock' => 1,
        'price' => 1
    ],
    'cookies' => [
        'stock' => 0,
        'price' => 1
    ]
]);

$tFactory = new TaskFactory( null );
$mainSequence = new IterateSelector();

// $firstSequence = new SequenceSelector();
// $firstSequence->addTask( $tFactory->entryExists( $bb->getEntry('counter') ) );
// $firstSequence->addTask( new DebugTraceTask( " [start counter] " ) );
// $firstSequence->addTask( $tFactory->loopUntilSuccess( new CountdownTask($bb)) );
// $firstSequence->addTask( new DebugTraceTask( " [end counter] <br>" ) );
// $firstSequence->addTask( $tFactory->call( function($dt) use ($bb) {
//     echo 'inside closure func';
//     return BehaviorTask::SUCCESS;
// } ));
// $firstSequence->addTask( $tFactory->removeEntry( $bb->getEntry( 'counter' ) ) );
// $mainSequence->addTask( $firstSequence );

// $prioritySequence = new PrioritySelector( $tFactory->taskVector([
//     new DoStuff("stuff 1"),
//     new DoStuff("stuff 2"),
//     new DoStuff("stuff 3") 
// ]) );

// $secondSequence = new SequenceSelector();
// $secondSequence->addTask( $tFactory->parallel([
//     new DoStuff(' parallel 1 '),
//     new DoStuff(' parallel 2 '),
//     new DoStuff(' parallel 3 '),
// ]) );
// $secondSequence->addTask( new ParallelSelector( ParallelSelector::ANY_SUCCESS, $tFactory->taskVector(
//     [
//         new DoStuff(' parallel 1 '),
//         new DoStuff(' parallel 2 '),
//         new DoStuff(' parallel 3 '),
//     ]
// ) ) );
// $secondSequence->addTask( new DebugTraceTask( " [ended do parallel stuff] " ) );
// $mainSequence->addTask( $secondSequence );

// $prioritySequence = new PrioritySelector( $tFactory->taskVector( [
//     new DoStuff(' priority 1 '),
//     new DoStuff(' priority 2 '),
//     new DoStuff(' priority 3 '),
// ] ));
// $mainSequence->addTask( $prioritySequence );

// $selector = new ParallelSelector( ParallelSelector::ALL_SUCCESS );
// $selector->addTask( new FunctionTask(
//     function() {
//         // echo "task 1";
//         return BehaviorTask::SUCCESS;
//     }
// ))
// ->addTask( new FunctionTask(
//     function() {
//         // echo "task 2";
//         return BehaviorTask::SUCCESS;
//     }
// ));
// $selector->addTask( new TestPredicate($bb) );
// $selector->addTask( new DebugTraceTask( "boid cannot seek" ) );
// $mainSequence->addTask( $selector );


$mainSequence->addTask( new DebugTraceTask( " [start] " ) );

$shoppingSequence = new SequenceSelector();
$shoppingSequence->addTask( $tFactory->not( new HasMilk($bb) ) )
->addTask( new NeedBuyProduct($bb, true) )
->addTask( $tFactory->and( [ new HasGas($bb), new HasMoney($bb) ] ))
->addTask( $tFactory->call( function() use ($bb) {
    // will drive to store
    $gas = $bb->getEntry('gas')->value();
    $money = $bb->getEntry('money')->value();
    // show gas and money status
    echo " [has: gas($gas) + money($money)] ";
    return BehaviorTask::SUCCESS;
}))
->addTask( $tFactory->parallel([
    new Drive($bb),
    new DebugTraceTask( " [cannot drive to store] ", BehaviorTask::FAIL )
], ParallelSelector::ANY_SUCCESS ))
->addTask( new DebugTraceTask( " [drive to store] " ) )
->addTask( $tFactory->storeEntry( $bb->getEntry('counter'), 10 ) )
->addTask( $tFactory->loopUntilSuccess(
    new CountdownTask( $bb )
) )
->addTask( new ShowGas($bb) )
->addTask( $tFactory->parallel([
    $tFactory->entryEquals( $bb->getEntry('store_open'), true ),
    new DebugTraceTask( " [store is open] " ),
], ParallelSelector::ANY_FAIL))
->addTask( $tFactory->parallel([
    $tFactory->entryEquals( $bb->getEntry('store_open'), true ),
    $tFactory->loopUntilFail(
        $tFactory->sequence(
            [
                new NeedBuyProduct($bb),
                new StoreHasProduct($bb),
                new BuyProduct($bb)
            ]
        )
    ),
], ParallelSelector::ANY_FAIL))
->addTask( $tFactory->parallel([
    $tFactory->entryEquals( $bb->getEntry('store_open'), true ),
    new DebugTraceTask( " [store is closed] " ),
], ParallelSelector::ANY_SUCCESS))
->addTask( $tFactory->parallel([
    new Drive($bb),
    new DebugTraceTask(  " [cannot drive home] ", BehaviorTask::FAIL )
], ParallelSelector::ANY_SUCCESS))
->addTask( new DebugTraceTask( " [drive home] " ) )
->addTask( $tFactory->storeEntry( $bb->getEntry('counter'), 10 ) )
->addTask( $tFactory->loopUntilSuccess(
    new CountdownTask( $bb )
) )
->addTask( new ShowGas($bb) );

$mainSequence->addTask( $shoppingSequence );

$mainSequence->addTask( $tFactory->parallel( [
    $tFactory->exitIf( new HasGas($bb), new DebugTraceTask( " [no gas] " )),
    $tFactory->exitIf( new HasMoney($bb), new DebugTraceTask( " [no money] " )),
], ParallelSelector::ALL_COMPLETE));

// $mainSequence->addTask( $tFactory->loopUntilSuccess(
//     new CountdownTask( $bb )
// ) );

$mainSequence->addTask( new DebugTraceTask( " [end] " ) );

$logicTree = new BehaviorTree( $tFactory->loopUntilComplete( $mainSequence ) );
// $logicTree = new BehaviorTree( $tFactory->loopUntilSuccess( $mainSequence ) );
// $logicTree = new BehaviorTree( $tFactory->loopForever( $mainSequence ) );
// $logicTree = new BehaviorTree( $tFactory->loopUntilFail( $mainSequence ) );
// $logicTree->debug = $logicTree->debugPrint = true;
// while(true) {
//    $logicTree->update(time());
// }

// $mainSequence->addTask( $tFactory->sequence([
//         $tFactory->storeEntry( $bb->getEntry('time'), microtime(true) * 100000 ),
//         new DelayTask( $bb->getEntry('time') ),
//         new DebugTraceTask( " [after delay] " )
//     ])
// );



// $mainSequence->addTask( $tFactory->selectRandomly([
//         new StoreEntryTask($bb->getEntry('text'), 'my text'),
//         new FunctionTask(
//             function() use ($bb) {
//                 echo $bb->getEntry('text')->value();
//                 return BehaviorTask::FAIL;
//             }
//         ),
//         new DebugTraceTask( " [after end] " ),
//     ])
// );

$result = null;
while ($result !== LoopingDecorator::SUCCESS) {
    $result = $logicTree->update(microtime(true));
}

// for ($i=0; $i < 10; $i++) { 
//     $result = $logicTree->update(time());
//     if($result == LoopingDecorator::SUCCESS) break;
// }

echo '<pre>';
//print_r($bb);
echo '</pre>';