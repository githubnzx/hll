<?php

namespace app\admin\logic;

class BaseLogic
{
    protected static $instance;

    public static function getInstance()
    {
//        self::$instance = new static();
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }

}
