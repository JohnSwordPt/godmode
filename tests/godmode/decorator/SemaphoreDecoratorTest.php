<?php

namespace tests\godmode\decorator;

use godmode\core\BehaviorTask;
use godmode\core\Semaphore;
use godmode\decorator\SemaphoreDecorator;
use PHPUnit\Framework\TestCase;

class SemaphoreDecoratorTest extends TestCase
{
    private $childTask;

    protected function setUp(): void
    {
        $this->childTask = $this->createMock(BehaviorTask::class);
    }

    public function testFailsWhenPermitNotAvailable()
    {
        $semaphore = new Semaphore('test', 1);
        $semaphore->acquire(); // Immediately use the only permit

        $this->childTask->expects($this->never())->method('update');

        $decorator = new SemaphoreDecorator($semaphore, $this->childTask);
        $status = $decorator->update(0);

        $this->assertEquals(BehaviorTask::FAIL, $status);
    }

    public function testSucceedsAndRunsChildWhenPermitIsAvailable()
    {
        $semaphore = new Semaphore('test', 1);
        $this->childTask->expects($this->once())->method('update')->willReturn(BehaviorTask::SUCCESS);

        $decorator = new SemaphoreDecorator($semaphore, $this->childTask);
        $status = $decorator->update(0);

        $this->assertEquals(BehaviorTask::SUCCESS, $status);
    }

    public function testHoldsPermitWhileActive()
    {
        $semaphore = new Semaphore('test', 1);

        // First decorator acquires the permit and keeps running
        $runningChild = $this->createMock(BehaviorTask::class);
        $runningChild->method('update')->willReturn(BehaviorTask::RUNNING);
        $decorator1 = new SemaphoreDecorator($semaphore, $runningChild);
        $status1 = $decorator1->update(0);
        $this->assertEquals(BehaviorTask::RUNNING, $status1);

        // Second decorator should fail because the permit is held
        $this->childTask->expects($this->never())->method('update');
        $decorator2 = new SemaphoreDecorator($semaphore, $this->childTask);
        $status2 = $decorator2->update(0);
        $this->assertEquals(BehaviorTask::FAIL, $status2);
    }

    public function testReleasesPermitOnReset()
    {
        $semaphore = new Semaphore('test', 1);

        // Decorator 1 acquires the permit
        $decorator1 = new SemaphoreDecorator($semaphore, $this->childTask);
        $decorator1->update(0);

        // Resetting decorator 1 should release the permit
        $decorator1->reset();

        // Decorator 2 should now be able to acquire the permit
        $this->childTask->expects($this->once())->method('update')->willReturn(BehaviorTask::SUCCESS);
        $decorator2 = new SemaphoreDecorator($semaphore, $this->childTask);
        $status2 = $decorator2->update(0);
        $this->assertEquals(BehaviorTask::SUCCESS, $status2);
    }
}
