<?php

namespace app\common\model;

class BaseModel
{
    protected static $instance;
    CONST IS_SHOW = 0;
    CONST IS_DEL = 1;

    public static function getInstance()
    {
        self::$instance = new static();
        return self::$instance;
    }

}