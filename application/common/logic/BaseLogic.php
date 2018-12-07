<?php

namespace app\common\logic;

class BaseLogic
{

    protected static $instance;

    public static function getInstance()
    {
        self::$instance = new static();
        return self::$instance;
    }

}
