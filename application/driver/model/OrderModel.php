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
use im\Easemob;
use think\Db;
use think\Log;
use think\Cache;
use think\Model;

class OrderModel extends BaseModel
{
    protected $orderTable = 'orders';
    protected $evaluatesTable = 'evaluates';

    const STATUS_DEL = 0;
    const ORDER_PAY_WX  = 1;
    const ORDER_PAY_ZFB = 2;

    public function orderFind($where = [], $fields = '*'){
        $where["is_del"] = OrderModel::STATUS_DEL;
        return Db::table($this->orderTable)->field($fields)->where($where)->find();
    }
    public function orderSelect($where = [], $fields = '*'){
        $where["is_del"] = OrderModel::STATUS_DEL;
        return Db::table($this->orderTable)->field($fields)->where($where)->select();
    }

    public function orderCount($where = [], $fields = '*'){
        $where["is_del"] = OrderModel::STATUS_DEL;
        return Db::table($this->orderTable)->field($fields)->where($where)->count();
    }

    public function orderEdit($where, $param){
        if(!isset($param["update_time"])){
            $param["update_time"] = CURR_TIME;
        }
        return Db::table($this->orderTable)->where($where)->update($param);
    }

    public function robbing($where, $param){
        Db::startTrans();
        try {
            // 删除redis订单数据
            if (Cache::store('driver')->has("RobOrderData:" . $where["id"])) {
                Cache::store('driver')->rm("RobOrderData:" . $where["id"]);
            }
            $this->orderEdit($where, $param);
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }

    public function orderList($where, $field = "*", $orders = ""){
        $where["is_del"] = OrderModel::STATUS_DEL;
        return Db::table($this->orderTable)->field($field)->where($where)->order($orders)->select();
    }


    public function orderInsert($order){
        Db::startTrans();
        try {
            $order_id = Db::table($this->orderTable)->insertGetId($order);             // 添加订单
            // 存入redis 判断熟人抢单
            $friend = explode(",", $order["driver_ids"]);
            foreach ($friend as $key => $value){
                Cache::store('user')->set("RobOrder:" . $value . $order_id, $order_id, 60);
            }
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }
    // 自动下单（针对预约订单）
    public function placeOrderEdit($order_id, $driver_ids){
        Db::startTrans();
        try {
            $this->orderEdit(["id"=>$order_id], ["is_place_order" => 0]); // 修改订单
            // 存入redis 判断熟人抢单
            $friend = explode(",", $driver_ids);
            foreach ($friend as $key => $value){
                Cache::store('user')->set("RobOrder:" . $value . $order_id, $order_id, 60);
            }
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }

    // 评论
    public function evaluateInfo($where, $fields = "*"){
        $where["is_del"] = self::IS_SHOW;
        return Db::table($this->evaluatesTable)->field($fields)->where($where)->find();
    }

    public function evaluateList($where, $fields = "*"){
        $where["is_del"] = self::IS_SHOW;
        return Db::table($this->evaluatesTable)->field($fields)->where($where)->select();
    }

    // 总评分
    public function evaluateColumn($where, $fields = "*"){
        $where["is_del"] = self::IS_SHOW;
        return Db::table($this->evaluatesTable)->where($where)->column($fields);
    }

}