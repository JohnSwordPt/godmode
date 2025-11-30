<?php

namespace ECSTests;

use ECS\System;
use ECS\SystemList;
use PHPUnit\Framework\TestCase;

class SystemListTest extends TestCase
{
    public function testAddSystemMaintainsPriorityOrder()
    {
        $s1 = $this->createMock(System::class);
        $s1->Priority = 10;
        
        $s2 = $this->createMock(System::class);
        $s2->Priority = 5;
        
        $s3 = $this->createMock(System::class);
        $s3->Priority = 20;

        $list = new SystemList();
        $list->addSystem($s1); // [10]
        $list->addSystem($s2); // [5, 10]
        $list->addSystem($s3); // [5, 10, 20]

        $this->assertCount(3, $list);
        $list->rewind();
        $this->assertSame($s2, $list->current());
        $list->next();
        $this->assertSame($s1, $list->current());
        $list->next();
        $this->assertSame($s3, $list->current());
    }

    public function testRemoveSystem()
    {
        $s1 = $this->createMock(System::class);
        $list = new SystemList();
        $list->addSystem($s1);
        
        $this->assertCount(1, $list);
        $list->Remove($s1);
        $this->assertCount(0, $list);
    }

    public function testGetReturnsCorrectSystem()
    {
        $s1 = new class extends System {}; // Anonymous class extending System
        $list = new SystemList();
        $list->addSystem($s1);

        $retrieved = $list->Get(get_class($s1));
        $this->assertSame($s1, $retrieved);
    }
}
