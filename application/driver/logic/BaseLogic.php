<?php
/**
 * Created by PhpStorm.
 * User: nzx
 * Date: 2018/09/01
 * Time: 上午14:00
 */

namespace app\driver\logic;


class BaseLogic
{
    protected static $instance;

    public static function getInstance()
    {
        self::$instance = new static();
        return self::$instance;
    }
}