<?php

namespace godmode\util;

use godmode\core\RandomStream;

/**
 * Provides utility routines to simplify obtaining randomized values.
 */
class Randoms
{
    protected $_stream;

    /**
     * Constructs a Randoms.
     */
    public function __construct(RandomStream $stream)
    {
        $this->_stream = $stream;
    }

    /**
     * Returns a pseudorandom, uniformly distributed integer value between 0 (inclusive) and high (exclusive).
     *
     * @param high the high value limiting the random number sought.
     *
     * @throws IllegalArgumentException if high is not positive.
     */
    public function getInt(int $high) : int
    {
        return $this->_stream->nextInt($high);
    }

    /**
     * Returns a pseudorandom, uniformly distributed integer value between low (inclusive) and high (exclusive).
     *
     * @throws IllegalArgumentException if high - low is not positive.
     */
    public function getInRange(int $low, int $high) : int
    {
        return $low + $this->_stream->nextInt($high - $low);
    }

    /**
     * Returns a pseudorandom, uniformly distributed number between 0.0 (inclusive) and high (exclusive).
     *
     * @param high the high value limiting the random number sought.
     */
    public function getNumber(float $high) : float
    {
        return $this->_stream->nextNumber() * $high;
    }

    /**
     * Returns a pseudorandom, uniformly distributed number between low (inclusive) and high (exclusive).
     */
    public function getNumberInRange(float $low, float $high) : float
    {
        return $low + ($this->_stream->nextNumber() * ($high - $low));
    }

    public function getChance($n) {
        return (0 == $this->_stream->nextInt($n));
    }

    public function getProbability($p) {
        return $this->_stream->nextNumber() < $p;
    }

    public function getBoolean() {
        return $this->getChance(2);
    }

    public function shuffle(Array $arr) {
        for ($ii = count($arr) - 1; $ii > 1; $ii--) {
            $idx1 = $ii - 1;
            $idx2 = $this->_stream->nextInt($ii);
            $tmp = $arr[$idx1];
            $arr[$idx1] = $arr[$idx2];
            $arr[$idx2] = $tmp;
        }
    }

    public function pick(Array $arr, $ifEmpty = null) {
        if ($arr === null || count($arr) === 0) {
            return $ifEmpty;
        }

        return $arr[$this->_stream->nextInt(count($arr))];
    }

    public function pluck(Array $arr, $ifEmpty = null) {
        if ($arr === null || count($arr) === 0) {
            return $ifEmpty;
        }

        return array_splice($arr, $this->_stream->nextInt(count($arr)), 1)[0];
    }

}
