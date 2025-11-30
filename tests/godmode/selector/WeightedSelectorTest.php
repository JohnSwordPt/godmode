<?php

namespace tests\godmode\selector;

use godmode\core\BehaviorTask;
use godmode\core\RandomStream;
use godmode\data\VectorWeightedTask;
use godmode\selector\WeightedSelector;
use godmode\selector\WeightedTask;
use PHPUnit\Framework\TestCase;

class WeightedSelectorTest extends TestCase
{
    public function testSelectsTaskBasedOnWeight()
    {
        // Create Mock RNG
        $rng = $this->createMock(RandomStream::class);
        
        // We want to force selection of the second task (Task B).
        // Task A: weight 10. Total=10. rand(10) < 10? Always true. Pick=A.
        // Task B: weight 20. Total=30. rand(30) < 20? We want TRUE.
        // rand(30) = nextNumber() * 30.
        // We need nextNumber() * 30 < 20 => nextNumber() < 0.666.
        // So if we return 0.5, it picks B.
        
        // Note: Randoms::getNumber() calls nextNumber() each time.
        // 1. For Task A: nextNumber() called.
        // 2. For Task B: nextNumber() called.
        
        $rng->method('nextNumber')->willReturnOnConsecutiveCalls(0.5, 0.5);

        $child1 = $this->createMock(BehaviorTask::class);
        $child2 = $this->createMock(BehaviorTask::class);
        $child2->method('update')->willReturn(BehaviorTask::SUCCESS);

        $wt1 = new WeightedTask($child1, 10);
        $wt2 = new WeightedTask($child2, 20);

        $tasks = new VectorWeightedTask($wt1, $wt2);
        $selector = new WeightedSelector($rng, $tasks);

        // Update should run selected task (B)
        $this->assertEquals(BehaviorTask::SUCCESS, $selector->update(0));
    }

    public function testFallsBackToOtherTaskIfFirstFails()
    {
        $rng = $this->createMock(RandomStream::class);
        
        // We want to force selection of A first, then it fails, then pick B.
        // Task A: weight 10.
        // Task B: weight 10.
        
        // Loop 1 (Selection):
        // 1. A (Total 10). rand(10) < 10. Always picks A.
        // 2. B (Total 20). rand(20) < 10. We want FALSE (Keep A).
        //    nextNumber() * 20 >= 10 => nextNumber() >= 0.5. Let's use 0.8.
        
        // Selection result: A.
        // A runs -> Returns FAIL.
        
        // Loop 2 (Selection excluding A):
        // Only B remains (Total 10). rand(10) < 10. Always picks B.
        
        $rng->method('nextNumber')->willReturnOnConsecutiveCalls(0.0, 0.8, 0.0);

        $child1 = $this->createMock(BehaviorTask::class);
        $child1->method('update')->willReturn(BehaviorTask::FAIL);
        
        $child2 = $this->createMock(BehaviorTask::class);
        $child2->method('update')->willReturn(BehaviorTask::SUCCESS);

        $wt1 = new WeightedTask($child1, 10);
        $wt2 = new WeightedTask($child2, 10);

        $tasks = new VectorWeightedTask($wt1, $wt2);
        $selector = new WeightedSelector($rng, $tasks);

        // Update should eventually succeed via B
        $this->assertEquals(BehaviorTask::SUCCESS, $selector->update(0));
    }
}
