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
namespace app\admin\model;

use app\admin\model\UserModel;
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

    public function truckList($where = [], $fields = '*', $page = ""){
        $where["is_del"] = TruckModel::STATUS_DEL;
        return Db::table($this->tableUser)->field($fields)->where($where)->page($page)->select();
    }

    public function truckTotal($where = [], $fields = '*'){
        $where["is_del"] = TruckModel::STATUS_DEL;
        return Db::table($this->tableUser)->field($fields)->where($where)->count();
    }

    public function truckAdd($data){
        $data["create_time"] = CURR_TIME;
        $data["update_time"] = CURR_TIME;
        return Db::table($this->tableUser)->insert($data);
    }

    public function truckEdit($where, $param){
        if (!isset($param["update_time"])) {
            $param["update_time"] = CURR_TIME;
        }
        return Db::table($this->tableUser)->where($where)->update($param);
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

    public function certAdd($data){
        $data["create_time"] = CURR_TIME;
        $data["update_time"] = CURR_TIME;
        return Db::table($this->certTable)->insert($data);
    }

    public function certAddAll($data){
        return Db::table($this->certTable)->insertAll($data);
    }

}