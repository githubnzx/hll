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
namespace app\user\model;

use app\admin\model\UserModel;
use app\user\logic\UserLogic;
use im\Easemob;
use think\Db;
use think\Log;
use think\Model;

class TruckModel extends BaseModel
{
    protected $tableUser = 'truck';
    protected $certTable = 'cert';

    const STATUS_DEL = 0;
    const CERT_TYPE  = 1;

    public function truckList($where = [], $fields = '*'){
        $where["is_del"] = TruckModel::STATUS_DEL;
        return Db::table($this->tableUser)->field($fields)->where($where)->select();
    }

    public function truckFind($where = [], $fields = '*'){
        $where["is_del"] = TruckModel::STATUS_DEL;
        return Db::table($this->tableUser)->field($fields)->where($where)->find();
    }

    public function certList($where, $fields = "*"){
        $result = Db::table($this->certTable)->field($fields)->where($where)->select() ?: [];
        foreach ($result as $key => $value){
            if($value["img"]) $result[$key]["img"] = handleImgPath($value["img"]);
        }
        return $result;
    }

    public function certFind($where, $fields = "*", $order = ""){
        $where["is_del"] = TruckModel::STATUS_DEL;
        $result = Db::table($this->certTable)->field($fields)->where($where)->order($order)->find();
        if(isset($result["img"])) $result["img"] = handleImgPath($result["img"]);
        return $result;
    }

}