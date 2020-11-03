<?php
declare(strict_types=1);

namespace UnionTypes\Spec\Fixtures;

use DateTime;

class Time extends DateTime
{
    /**
     * Return `today` datetime.
     *
     * @return \UnionTypes\Spec\Fixtures\Time Today datetime.
     */
    public static function today(): Time
    {
        return new Time();
    }
}
