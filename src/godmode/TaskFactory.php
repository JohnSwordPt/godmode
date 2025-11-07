<?php

namespace godmode;

use godmode\core\BehaviorTask;
use godmode\core\RandomStream;
use godmode\core\Semaphore;
use godmode\core\TimeKeeper;
use godmode\data\Entry;
use godmode\data\MutableEntry;
use godmode\data\VectorBehaviorPredicate;
use godmode\data\VectorBehaviorTask;
use godmode\data\VectorScopedResource;
use godmode\data\VectorWeightedTask;
use godmode\decorator\DelayFilter;
use godmode\decorator\LoopingDecorator;
use godmode\decorator\PredicateFilter;
use godmode\decorator\ScopeDecorator;
use godmode\decorator\SemaphoreDecorator;
use godmode\pred\AndPredicate;
use godmode\pred\BehaviorPredicate;
use godmode\pred\EntryEqualsPred;
use godmode\pred\EntryExistsPred;
use godmode\pred\EntryNotExistsPred;
use godmode\pred\FunctionPredicate;
use godmode\pred\NotPredicate;
use godmode\pred\OrPredicate;
use godmode\selector\ParallelSelector;
use godmode\selector\PrioritySelector;
use godmode\selector\SequenceSelector;
use godmode\selector\WeightedSelector;
use godmode\selector\WeightedTask;
use godmode\task\DelayTask;
use godmode\task\FunctionTask;
use godmode\task\NoOpTask;
use godmode\task\RemoveEntryTask;
use godmode\task\StoreEntryTask;

class TaskFactory {

    /** @var TimeKeeper $_timeKeeper */
    protected $_timeKeeper;

    /**
     * TaskFactory constructor.
     * @param TimeKeeper $timeKeeper
     */
    public function __construct( $timeKeeper ) {
        $this->_timeKeeper = $timeKeeper;
    }

    /**
     * Runs the given task while the predicate is true.
     * @param BehaviorPredicate $pred The predicate to test.
     * @param BehaviorTask $task The task to run.
     * @return PredicateFilter
     */
    public function runWhile( BehaviorPredicate $pred, BehaviorTask $task) : PredicateFilter {
        return new PredicateFilter( $pred, $task );
    }

    /**
     * Runs the given task if the predicate/task is true/returns SUCCESS.
     * The predicate is only evaluated before entering the task.
     * @param BehaviorPredicate $pred The predicate to test.
     * @param BehaviorTask $task The task to run.
     * @return SequenceSelector
     */
    public function enterIf( BehaviorPredicate $pred, BehaviorTask $task ) : SequenceSelector {
        return $this->sequence( [$pred, $task] );
    }

    /**
     * Stops running the task if the predicate is true.
     * @param BehaviorPredicate $pred The predicate to test.
     * @param BehaviorTask $task The task to run.
     * @return PredicateFilter
     */
    public function exitIf( BehaviorPredicate $pred, BehaviorTask $task ) : PredicateFilter {
        return $this->runWhile( $this->not( $pred ), $task );
    }
    
    /**
     * Runs the given task, using the given ScopedResource. The resource will be acquired
     * before the task is run, and released when the task is complete (or gets interrupted).
     *
     * (This is similar to a 'using' statement (or try/finally) in a structured language.)
     * @param mixed $resource The resource to use.
     * @param BehaviorTask $task The task to run.
     * @return ScopeDecorator|null
     */
    public function using( $resource, BehaviorTask $task ) {
        // combine multiple using(resource1, using(resource2, ... statements into one
        if ( $task instanceof ScopeDecorator ) {
            $using = new ScopeDecorator( $task );
            $using->addResource( $resource );
            return $using;

        } else {
            return new ScopeDecorator( $task, new VectorScopedResource( $resource ) );
        }
        return null;
    }

    /**
     * Runs children in sequence until one fails, or all succeed.
     * @param BehaviorTask[] $children The children to run.
     * @return SequenceSelector
     */
    public function sequence( array $children ) : SequenceSelector {
        // reuse existing task if possible
        if ( count($children) > 0 && $children[ 0 ] instanceof SequenceSelector ) {
            $seq = $children[ 0 ];
            for ( $ii = 1; $ii < count($children); ++$ii ) {
                $seq->addTask( $children[ $ii ] );
            }
            return $seq;

        } else {
            return new SequenceSelector( $this->taskVector( $children ) );
        }
    }

    /**
     * Runs all children concurrently until one fails.
     *
     * @param BehaviorTask[] $children The list of tasks
     * @param int|null $type Default is all successful
     * @return ParallelSelector
     */
    public function parallel( array $children, $type = null ) : ParallelSelector {
        if($type === null) $type = ParallelSelector::ALL_SUCCESS;

        // reuse existing task if possible
        if ( count($children) > 0 && $children[ 0 ] instanceof ParallelSelector && ( $children[ 0 ] )->{'type'} == $type ) {
            $parallel = $children[ 0 ];
            for ( $ii = 1; $ii < count($children); ++$ii ) {
                $parallel->addTask( $children[ $ii ] );
            }
            return $parallel;
        } else {
            return new ParallelSelector( $type, $this->taskVector( $children ) );
        }
    }

    /**
     * Runs a task a specified number of times.
     * @param int $count The number of times to run the task.
     * @param BehaviorTask $task The task to run.
     * @return BehaviorTask
     */
    public function loop( int $count, BehaviorTask $task ) : BehaviorTask {
        return new LoopingDecorator( LoopingDecorator::BREAK_NEVER, $count, $task );
    }

    /**
     * Loops a task forever.
     * @param BehaviorTask $task The task to loop.
     * @return LoopingDecorator
     */
    public function loopForever( BehaviorTask $task ) : LoopingDecorator {
        return new LoopingDecorator( LoopingDecorator::BREAK_NEVER, 0, $task );
    }

    /**
     * Runs a task until it succeeds.
     * @param BehaviorTask $task The task to run.
     * @return LoopingDecorator
     */
    public function loopUntilSuccess( BehaviorTask $task  ) : LoopingDecorator {
        return new LoopingDecorator( LoopingDecorator::BREAK_ON_SUCCESS, 0, $task );
    }

    /**
     * Loops a task until it fails.
     * @param BehaviorTask $task The task to loop.
     * @return LoopingDecorator
     */
    public function loopUntilFail( BehaviorTask $task ) : LoopingDecorator {
        return new LoopingDecorator( LoopingDecorator::BREAK_ON_FAIL, 0, $task );
    }

    /**
     * Loops a task until it succeeds or fails.
     * @param BehaviorTask $task The task to loop.
     * @return LoopingDecorator
     */
    public function loopUntilComplete( BehaviorTask $task ) : LoopingDecorator {
        return new LoopingDecorator( LoopingDecorator::BREAK_ON_COMPLETE, 0, $task );
    }

    /**
     * Runs a task, and ensure that it won't be re-run until a minimum amount of time has elapsed.
     * @param Entry $minDelay The minimum delay between runs.
     * @param BehaviorTask $task The task to run.
     * @return DelayFilter
     */
    public function withRepeatDelay( Entry $minDelay, BehaviorTask $task ) : DelayFilter {
        return new DelayFilter( $minDelay, $this->_timeKeeper, $task );
    }

    /**
     * Runs the first task that returns a non-FAIL status.
     * Higher-priority tasks (those higher in the list) can interrupt lower-priority tasks that
     * are running.
     * @param BehaviorTask[] $children The children to select from.
     * @return PrioritySelector
     */
    public function selectWithPriority( $children ) : PrioritySelector {
        return new PrioritySelector( $this->taskVector( $children ) );
    }

    /**
     * Randomly selects a task to run.
     * @param RandomStream $rng The random number generator to use.
     * @param array $childrenAndWeights The children and their weights.
     * @return WeightedSelector
     */
    public function selectRandomly( RandomStream $rng, array $childrenAndWeights ) : WeightedSelector {
        $n = count($childrenAndWeights);
        $children = new VectorWeightedTask();
        for ( $ii = 0; $ii < $n; $ii += 2 ) {
            $children->push( new WeightedTask( $childrenAndWeights[ $ii ], $childrenAndWeights[ $ii + 1 ] ) );
        }
        return new WeightedSelector( $rng, $children );
    }

    /**
     * Wait a specified amount of time.
     * @param Entry $time The time to wait.
     * @return DelayTask
     */
    public function wait( Entry $time ) : DelayTask {
        return new DelayTask( $time );
    }

    /**
     * Calls a function.
     * @param callable $f The function to call.
     * @return FunctionTask
     */
    public function call( $f ) : FunctionTask {
        return new FunctionTask( $f );
    }

    /**
     * Runs a task if the given semaphore is successfully acquired.
     * @param Semaphore $semaphore The semaphore to acquire.
     * @param BehaviorTask $task The task to run.
     * @return SemaphoreDecorator
     */
    public function withSemaphore( Semaphore $semaphore, BehaviorTask $task ) : SemaphoreDecorator {
        return new SemaphoreDecorator( $semaphore, $task );
    }

    /**
     * Removes the given value from its blackboard.
     * @param MutableEntry $entry The entry to remove.
     * @return RemoveEntryTask
     */
    public function removeEntry( MutableEntry $entry ) : RemoveEntryTask {
        return new RemoveEntryTask( $entry );
    }

    /**
     * Stores a value in the blackboard.
     * @param MutableEntry $entry The entry to store.
     * @param mixed $storeVal The value to store.
     * @return StoreEntryTask
     */
    public function storeEntry( MutableEntry $entry, $storeVal ) : StoreEntryTask {
        return new StoreEntryTask( $entry, $storeVal );
    }

    /**
     * Does nothing.
     * @return BehaviorTask
     */
    public function noOp() {
        return new NoOpTask(BehaviorTask::SUCCESS);
    }

    /**
     * Returns !pred.
     * @param BehaviorPredicate $pred The predicate to negate.
     * @return BehaviorPredicate
     */
    public function not( BehaviorPredicate $pred ) : BehaviorPredicate {
        // return ( pred is NotPredicate ? NotPredicate( pred ).pred : new NotPredicate( pred ) );
        return ($pred instanceof NotPredicate) ? $pred->getPred() : new NotPredicate( $pred );
    }

    /**
     * ANDs the given preds together.
     * @param BehaviorPredicate[] $preds The predicates to AND.
     * @return AndPredicate
     */
    public function and( $preds ) : AndPredicate {
        // re-use existing predicate if possible
        if ( count($preds) > 0 && $preds[ 0 ] instanceof AndPredicate ) {
            $parent = $preds[ 0 ];
            for ( $ii = 0; $ii < count($preds); ++$ii ) {
                $parent->addPred( $preds[ $ii ] );
            }
            return $parent;

        } else {
            return new AndPredicate( $this->predVector( $preds ) );
        }
    }

    /**
     * ORs the given preds together.
     * @param BehaviorPredicate[] $preds The predicates to OR.
     * @return OrPredicate
     */
    public function or( $preds ) : OrPredicate {
        // re-use existing predicate if possible
        if ( count($preds) > 0 && $preds[ 0 ] instanceof OrPredicate ) {
            $parent = $preds[ 0 ];
            for ( $ii = 0; $ii < count($preds); ++$ii ) {
                $parent->addPred( $preds[ $ii ] );
            }
            return $parent;

        } else {
            return new OrPredicate( $this->predVector( $preds ) );
        }
    }

    /**
     * Returns a Predicate that calls the given function.
     * @param callable $f The function to call.
     * @return FunctionPredicate
     */
    public function pred( $f ) : FunctionPredicate {
        return new FunctionPredicate( $f );
    }

    /**
     * Tests the existence of the given entry in its blackboard.
     * @param Entry $value The entry to test.
     * @return EntryExistsPred
     */
    public function entryExists( Entry $value ) : EntryExistsPred {
        return new EntryExistsPred( $value );
    }

    /**
     * Tests if the given entry does not exist in its blackboard.
     * @param Entry $value The entry to test.
     * @return EntryNotExistsPred
     */
    public function entryNotExists( Entry $value ) : EntryNotExistsPred {
        return new EntryNotExistsPred( $value );
    }

    /**
     * Tests if the given entry has the given value.
     * @param Entry $entry The entry to test.
     * @param mixed $value The value to test against.
     * @return EntryEqualsPred
     */
    public function entryEquals( Entry $entry, $value ) : EntryEqualsPred {
        return new EntryEqualsPred( $entry, $value );
    }

    /**
     * Convert array to array of BehaviorTasks.
     *
     * @param array $arr
     * @return VectorBehaviorTask
     */
    public function taskVector( $arr ) : VectorBehaviorTask {
        // $n = count($arr);
        // $out = new VectorBehaviorTask();
        // for ($ii = 0; $ii < $n; $ii++) {
        //     $out->append($arr[$ii]);
        // }
        $out = new VectorBehaviorTask();
        $out->exchangeArray($arr);
        return $out;
    }

    /**
     * Convert array to array of BehaviorPredicates.
     *
     * @param array $arr
     * @return VectorBehaviorPredicate
     */
    public function predVector( $arr ) : VectorBehaviorPredicate {
        // $n = count($arr);
        // $out = new VectorBehaviorPredicate();
        // for ($ii = 0; $ii < $n; $ii++) {
        //     $out->append($arr[$ii]);
        // }
        $out = new VectorBehaviorPredicate();
        $out->exchangeArray($arr);
        return $out;
    }
    
}