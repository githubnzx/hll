<?php
// +----------------------------------------------------------------------
// | snake
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 http://baiyf.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: NickBai <1902822973@qq.com>
// +----------------------------------------------------------------------
namespace app\driver\model;

use app\user\logic\UserLogic;
use app\user\model\TruckModel;
use im\Easemob;
use think\Db;
use think\Log;
use think\Model;

class MemberModel extends BaseModel
{
    protected $tableUser = 'member';


    const STATUS_DEL = 0;
    const USER_TYPE_USER    = 1;

    const MEMBER_USE_STATUS = 1;



    public function memberFind($where, $fields = '*'){
        $where["is_del"] = self::STATUS_DEL;
        $where["status"] = self::MEMBER_USE_STATUS;
        return Db::table($this->tableUser)->field($fields)->where($where)->find();
    }

    public function isMember($where, $fields = '*'){
        $where["is_del"] = self::STATUS_DEL;
        $where["status"] = self::MEMBER_USE_STATUS;
        $return = Db::table($this->tableUser)->field("id")->where($where)->find();
        if(isset($return["id"]) && $return["id"]) {
            return true;
        } else {
            return false;
        }
    }

}