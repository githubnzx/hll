<?php
/**
 * Created by PhpStorm.
 * User: php_s
 * Date: 2018/2/2
 * Time: 14:42
 */

namespace app\user\model;
use const Grpc\STATUS_ABORTED;
use think\Db;
use think\exception\HttpException;
use app\common\sms\UserSms;
use app\common\sms\CoachSms;
use think\Cache;
use think\Log;

class AdviseModel extends BaseModel
{
    private $table  = 'advise';

    const USER_TYPE = 1;
    const IS_DEL    = 0;

    public function adviseAdd($param)
    {
        if (!isset($param['create_time'])) {
            $param['create_time'] = CURR_TIME;
        }
        if (!isset($param['update_time'])) {
            $param['update_time'] = CURR_TIME;
        }
        return Db::table($this->table)->insert($param);
    }
    public function adviseFind($where, $field = "*")
    {
        $where["is_del"] = AdviseModel::IS_DEL;
        return Db::table($this->table)->field($field)->where($where)->find();
    }
}