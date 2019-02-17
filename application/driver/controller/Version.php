<?php
/**
 * Created by PhpStorm.
 * User: php_s
 * Date: 2018/5/15
 * Time: 15:29
 */

namespace app\driver\controller;

use app\driver\model\VersionModel;

ob_clean();

class Version extends Base
{
    // 版本
    public function index()
    {
        $info = VersionModel::getInstance()->versionFind(["type"=>VersionModel::VERSION_TYPE], "title, number, version_url, describe, status, path");
        return success_out($info ?: []);
    }
}