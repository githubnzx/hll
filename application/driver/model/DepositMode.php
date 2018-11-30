<?php
namespace app\driver\model;

use app\driver\model\DriverModel;
use app\driver\logic\OrderLogic;
use think\Db;
use think\Log;
use think\Model;
use think\Cache;

class DepositMode extends BaseModel
{
    protected $depositOrder = 'deposit_order';

    const STATUS_DEL = 0;
    const USER_TYPE_USER = 1;
    const OPERATE_TYPE_JYJ = 1;
    const OPERATE_TYPE_TYJ = 2;

    public function depositOrderAddGetId($data){
        $curr_time = CURR_TIME;
        $data['create_time'] = $curr_time;
        $data['update_time'] = $curr_time;
        return Db::table($this->depositOrder)->insertGetId($data);
    }

    public function retreat($order, $openid){
        Db::startTrans();
        try {
            $this->depositOrderAddGetId($order);
            // 退钱
            if ($order["pay_type"] === 1) {
                $order = OrderLogic::getInstance()->transferWx($order['code'], $openid, $order['price']);
                if($order['return_code'] != 'SUCCESS' || $order['result_code'] != 'SUCCESS'){
                    return false;
                }
            } else { // 支付宝
                $order = OrderLogic::getInstance()->refundZfb($order['code'], $order['price'], "押金退款");
            }
            // 修改用户数据
            DriverModel::getInstance()->userEdit(["id"=>$order["user_id"]], ["deposit_status"=>0, "deposit_price"=>"0.00", "deposit_number"=>0, "deposit_pay_type"=>0]);
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }

    public function depositOrderFind($where, $field = "*"){
        $where['is_del'] = self::STATUS_DEL;
        return Db::table($this->depositOrder)->field($field)->where($where)->find();
    }

    public function depositOrderUp($where, $param){
        if(!isset($param["update_time"])){
            $param["update_time"] = CURR_TIME;
        }
        return Db::table($this->depositOrder)->where($where)->update($param);
    }


    // 修改deposit_order
    public function depositOrderEdit($order, $pay_type){
        Db::startTrans();
        try {
            $this->depositOrderUp(["id"=>$order["id"]], ["status"=>2]);
            $depositNumber  = DriverModel::getInstance()->userFind(["id"=>$order["user_id"]], "deposit_number")["deposit_number"] ?: 0;
            $deposit_number = bcadd($depositNumber, 1);
            DriverModel::getInstance()->userEdit(["id"=>$order["user_id"]], ["deposit_status"=>1, "deposit_pay_type"=>$pay_type, "price"=>$order["price"], "deposit_number"=>$deposit_number]);
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }

}