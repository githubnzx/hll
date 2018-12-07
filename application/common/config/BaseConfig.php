<?php
/**
 * Created by PhpStorm.
 * User: wangaojie
 * Date: 2017/11/23
 * Time: 上午11:38
 */

namespace app\common\config;

class BaseConfig
{
    protected static $instance;

    public static function getInstance()
    {
        self::$instance = new static();
        return self::$instance;
    }
}