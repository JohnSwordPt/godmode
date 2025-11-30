<?php

namespace tests\godmode\decorator;

use godmode\core\BehaviorTask;
use godmode\core\SystemTimeKeeper;
use godmode\data\StaticEntry;
use godmode\decorator\DelayFilter;
use PHPUnit\Framework\TestCase;

class DelayFilterTest extends TestCase
{
    private $timeKeeper;
    private $childTask;

    protected function setUp(): void
    {
        $this->timeKeeper = new SystemTimeKeeper();
        $this->childTask = $this->createMock(BehaviorTask::class);
    }

    public function testRunsFirstTimeSuccessfully()
    {
        $this->childTask->method('update')->willReturn(BehaviorTask::SUCCESS);
        $delay = new StaticEntry(100.0);
        $filter = new DelayFilter($delay, $this->timeKeeper, $this->childTask);

        $status = $filter->update(0);
        $this->assertEquals(BehaviorTask::SUCCESS, $status);
    }

    public function testFailsIfCalledWithinDelayPeriod()
    {
        $this->childTask->method('update')->willReturn(BehaviorTask::SUCCESS);
        $delay = new StaticEntry(100.0);
        $filter = new DelayFilter($delay, $this->timeKeeper, $this->childTask);

        // First run succeeds
        $filter->update(0);

        // Advance time by less than the delay
        $this->timeKeeper->advanceTime(50.0);

        // Second run should fail
        $status = $filter->update(0);
        $this->assertEquals(BehaviorTask::FAIL, $status);
    }

    public function testSucceedsIfCalledAfterDelayPeriod()
    {
        $this->childTask->method('update')->willReturn(BehaviorTask::SUCCESS);
        $delay = new StaticEntry(100.0);
        $filter = new DelayFilter($delay, $this->timeKeeper, $this->childTask);

        // First run succeeds
        $filter->update(0);
        
        // Advance time by more than the delay
        $this->timeKeeper->advanceTime(101.0);

        // Second run should succeed
        $status = $filter->update(0);
        $this->assertEquals(BehaviorTask::SUCCESS, $status);
    }
    
    public function testReturnsRunningAndActivatesDelayOnCompletion()
    {
        $this->childTask->expects($this->exactly(2))
            ->method('update')
            ->will($this->onConsecutiveCalls(BehaviorTask::RUNNING, BehaviorTask::SUCCESS));
            
        $delay = new StaticEntry(100.0);
        $filter = new DelayFilter($delay, $this->timeKeeper, $this->childTask);

        // First update, child returns RUNNING
        $status = $filter->update(0);
        $this->assertEquals(BehaviorTask::RUNNING, $status);

        // Second update, child returns SUCCESS
        $status = $filter->update(0);
        $this->assertEquals(BehaviorTask::SUCCESS, $status);
        
        // Third update, should fail due to delay
        $status = $filter->update(0);
        $this->assertEquals(BehaviorTask::FAIL, $status);
    }
}
