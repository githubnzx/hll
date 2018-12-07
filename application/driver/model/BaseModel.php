<?php
/**
 * Created by PhpStorm.
 * User: php_s
 * Date: 2018/2/7
 * Time: 11:14
 */

namespace app\driver\model;


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