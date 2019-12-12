<?php
declare(strict_types=1);

namespace UnionTypes\Spec\Fixtures;

class Math
{
    /**
     * Return a random number between 0 and 10.
     *
     * ### Note
     * - Used only for test purpose.
     *
     * @return int A random integer between `0` and `10`.
     */
    public static function random()
    {
        return rand(0, 10);
    }

    /**
     * Return the sum of `a+b`.
     *
     * ### Note
     * - Used only for test purpose.
     *
     * @param int|float $a The first number.
     * @param int|float $b The second number.
     * @return int|float The total of `a+b`.
     */
    public static function add($a, $b)
    {
        return $a + $b;
    }
}
