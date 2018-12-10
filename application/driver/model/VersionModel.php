<?php
/**
 * Created by PhpStorm.
 * User: php_s
 * Date: 2018/2/2
 * Time: 14:42
 */

namespace app\driver\model;


use think\Db;

class VersionModel extends BaseModel
{
    CONST IS_DEL = 0;//0:正常 1:用户删除 2:后台删除
    CONST VERSION_TYPE = 2;

    private $table = 'version';

    public function versionFind($where, $field='*'){
        $where["is_del"] = self::IS_DEL;
        $info = Db::table($this->table)->field($field)->where($where)->find();
        $path = config('apk.path') . $info['path'];
        if (is_file($path)) {
            $info['encryption'] = md5_file($path);
            $info['file_size'] = filesize($path);
        } else {
            $info['encryption'] = '';
            $info['file_size'] = '';
        }
        unset($info['path']);
        return $info;
    }
}

