<?php

namespace AlibabaCloud\Credentials\Tests;

class Helper
{
    public static function getEnvironment($name, $default = "")
    {
        $res = \getenv($name);
        if (false === $res) {
            return $default;
        }
        return $res;
    }
}
