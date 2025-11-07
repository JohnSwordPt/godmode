<?php

namespace godmode\decorator;

use godmode\core\BehaviorTask;
use PHPUnit\Framework\TestCase;

class LoopingDecoratorTest extends TestCase
{
    public function testBreakOnSuccess()
    {
        $task = $this->createMock(BehaviorTask::class);
        $task->method('update')
             ->will($this->onConsecutiveCalls(BehaviorTask::RUNNING, BehaviorTask::RUNNING, BehaviorTask::SUCCESS));

        /** @var BehaviorTask $task */
        $decorator = new LoopingDecorator(LoopingDecorator::BREAK_ON_SUCCESS, 0, $task);

        $this->assertEquals(BehaviorTask::RUNNING, $decorator->update(0.0));
        $this->assertEquals(BehaviorTask::RUNNING, $decorator->update(0.0));
        $this->assertEquals(BehaviorTask::SUCCESS, $decorator->update(0.0));
    }

    public function testBreakOnFail()
    {
        $task = $this->createMock(BehaviorTask::class);
        $task->method('update')
             ->will($this->onConsecutiveCalls(BehaviorTask::RUNNING, BehaviorTask::RUNNING, BehaviorTask::FAIL));

        /** @var BehaviorTask $task */
        $decorator = new LoopingDecorator(LoopingDecorator::BREAK_ON_FAIL, 0, $task);

        $this->assertEquals(BehaviorTask::RUNNING, $decorator->update(0.0));
        $this->assertEquals(BehaviorTask::RUNNING, $decorator->update(0.0));
        $this->assertEquals(BehaviorTask::SUCCESS, $decorator->update(0.0)); // Decorator returns SUCCESS when break condition is met
    }

    public function testBreakOnComplete()
    {
        $task = $this->createMock(BehaviorTask::class);
        $task->method('update')
             ->will($this->onConsecutiveCalls(BehaviorTask::RUNNING, BehaviorTask::SUCCESS));

        /** @var BehaviorTask $task */
        $decorator = new LoopingDecorator(LoopingDecorator::BREAK_ON_COMPLETE, 0, $task);

        $this->assertEquals(BehaviorTask::RUNNING, $decorator->update(0.0));
        $this->assertEquals(BehaviorTask::SUCCESS, $decorator->update(0.0));
    }

    public function testBreakNever()
    {
        $task = $this->createMock(BehaviorTask::class);
        $task->method('update')->willReturn(BehaviorTask::SUCCESS);

        /** @var BehaviorTask $task */
        $decorator = new LoopingDecorator(LoopingDecorator::BREAK_NEVER, 0, $task);

        $this->assertEquals(BehaviorTask::RUNNING, $decorator->update(0.0));
        $this->assertEquals(BehaviorTask::RUNNING, $decorator->update(0.0));
        $this->assertEquals(BehaviorTask::RUNNING, $decorator->update(0.0));
    }

    public function testFixedLoopCount()
    {
        $task = $this->createMock(BehaviorTask::class);
        $task->method('update')->willReturn(BehaviorTask::SUCCESS);

        /** @var BehaviorTask $task */
        $decorator = new LoopingDecorator(LoopingDecorator::BREAK_NEVER, 3, $task);

        $this->assertEquals(BehaviorTask::RUNNING, $decorator->update(0.0)); // loop 1
        $this->assertEquals(BehaviorTask::RUNNING, $decorator->update(0.0)); // loop 2
        $this->assertEquals(BehaviorTask::RUNNING, $decorator->update(0.0)); // loop 3
        $this->assertEquals(BehaviorTask::SUCCESS, $decorator->update(0.0)); // loop 4, breaks
    }

    public function testReset()
    {
        $task = $this->createMock(BehaviorTask::class);
        $task->method('update')->willReturn(BehaviorTask::SUCCESS);
        $task->expects($this->atLeastOnce())->method('deactivate');
        
        /** @var BehaviorTask $task */
        $decorator = new LoopingDecorator(LoopingDecorator::BREAK_NEVER, 2, $task);
        $decorator->update(0.0);
        
        $decorator->reset();
        // after reset, the loop count should be 0
        $this->assertEquals(BehaviorTask::RUNNING, $decorator->update(0.0));
        $this->assertEquals(BehaviorTask::RUNNING, $decorator->update(0.0));
        $this->assertEquals(BehaviorTask::SUCCESS, $decorator->update(0.0));
    }
}
