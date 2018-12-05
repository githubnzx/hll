<?php
/**
 * Created by PhpStorm.
 * User: php_s
 * Date: 2018/5/15
 * Time: 15:29
 */

namespace app\user\controller;

use app\user\model\VersionModel; 

class Version extends Base
{
    // 版本
    public function index()
    {
        $info = VersionModel::getInstance()->versionFind(["type"=>VersionModel::VERSION_TYPE], "title, number, version_url, describe, status");
        return success_out($info ?: []);
    }
}