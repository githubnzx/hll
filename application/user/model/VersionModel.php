<?php
/**
 * Created by PhpStorm.
 * User: php_s
 * Date: 2018/2/2
 * Time: 14:42
 */

namespace app\user\model;


use think\Db;

class VersionModel extends BaseModel
{
    CONST IS_DEL = 0;//0:正常 1:用户删除 2:后台删除
    CONST VERSION_TYPE = 1;

    private $table = 'version';

    public function versionFind($where, $field='*'){
        $where["is_del"] = self::IS_DEL;
        return Db::table($this->table)->field($field)->where($where)->find();
    }
}

