<?php

namespace tests\godmode\util;

use godmode\core\RandomStream;
use godmode\util\Randoms;
use PHPUnit\Framework\TestCase;

class RandomsTest extends TestCase
{
    public function testGetIntDelegatesToStream()
    {
        $stream = $this->createMock(RandomStream::class);
        $stream->expects($this->once())
            ->method('nextInt')
            ->with(10)
            ->willReturn(5);

        $randoms = new Randoms($stream);
        $this->assertEquals(5, $randoms->getInt(10));
    }

    public function testGetNumberDelegatesToStream()
    {
        $stream = $this->createMock(RandomStream::class);
        $stream->expects($this->once())
            ->method('nextNumber')
            ->willReturn(0.5);

        $randoms = new Randoms($stream);
        // getNumber(100) => 0.5 * 100 = 50
        $this->assertEquals(50.0, $randoms->getNumber(100.0));
    }
    
    public function testShuffleModifiesArray()
    {
        $stream = $this->createMock(RandomStream::class);
        $stream->method('nextInt')->willReturn(0);

        $randoms = new Randoms($stream);
        $arr = [1, 2, 3];
        
        $randoms->shuffle($arr);
        
        // With fix, [1, 2, 3] -> swap index 1(2) with 0(1) -> [2, 1, 3]
        $this->assertEquals([2, 1, 3], $arr);
    }
}