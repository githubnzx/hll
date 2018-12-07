<?php

namespace app\common\push;

class BaseLogic
{

    protected static $instance;

    public static function getInstance()
    {
        self::$instance = new static();
        return self::$instance;
    }

}
