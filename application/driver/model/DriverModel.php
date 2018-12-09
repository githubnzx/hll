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

use app\common\sms\DriverSms;
use app\driver\logic\OrderLogic;
use app\user\logic\UserLogic;
use app\user\model\TruckModel;
use im\Easemob;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use think\Db;
use think\Log;
use think\Model;

class DriverModel extends BaseModel
{
    protected $tableUser = 'driver';
    protected $wechatTable = 'wechat';
    protected $certTable   = 'cert';
    protected $bill_table  = 'bill';
    protected $billWithdraw= 'bill_withdraw';
    protected $balance  = 'balance';
    protected $rechargeOrder  = 'recharge_order';


    const STATUS_DEL = 0;
    const USER_TYPE_USER    = 2;
    const INTEGRAL_REGISTER = 10;
    const TYPE_IN  = 1;
    const TYPE_OUT = 2;
    const USER_type= 1;

    const STAY_PAY = 1;

    const CARD_JUST = 4;
    const CARD_BACK = 5;
    const JS_CARD   = 6;
    const XS_CARD   = 7;
    const DRIVER_CAR  = 8;
    const DEPOSIT_OPERATE_TYPE = 1;
    const RETREAT_OPERATE_TYPE = 2;


    public function userFind($where, $fields = '*'){
        $result = Db::table($this->tableUser)->field($fields)->where($where)->find();
        if(isset($result["icon"]) && $result["icon"]){
            $result["icon"] = handleImgPath($result["icon"]);
        }
        return $result;
    }

    public function userBindAll($where, $fields = '*'){
        return Db::table($this->tableUser)->field($fields)->where($where)->select();
    }

    public function userEdit($where, $param){
        if(!isset($param["update_time"])){
            $param["update_time"] = CURR_TIME;
        }
        return Db::table($this->tableUser)->where($where)->update($param);
    }

    public function userInsert($data){
        $data['create_time'] = CURR_TIME;
        $data['update_time'] = CURR_TIME;
        return Db::table($this->tableUser)->insertGetId($data);
    }

    // 添加充值订单
    public function rechargeOrderInsert($data){
        $user['create_time'] = CURR_TIME;
        $user['update_time'] = CURR_TIME;
        return Db::table($this->rechargeOrder)->insertGetId($data);
    }

    // 修改充值订单
    public function rechargeOrdeEdit($where, $param){
        if(!isset($param["update_time"])){
            $param["update_time"] = CURR_TIME;
        }
        return Db::table($this->rechargeOrder)->where($where)->update($param);
    }

    // 查询充值订单
    public function rechargeOrderFind($where, $field = "*"){
        $where["is_del"] = self::STATUS_DEL;
        return Db::table($this->rechargeOrder)->field($field)->where($where)->find();
    }

    // 用户注册
    public function userAdd($data){
        Db::startTrans();
        try {
            $user_id = $this->userInsert($data);
            // 我的积分
            IntegralModel::getInstance()->userIntegralAdd(["user_id"=>$user_id, "integral"=>DriverModel::INTEGRAL_REGISTER, "user_type"=>DriverModel::USER_TYPE_USER]);
            // 积分记录
            $record["user_id"] = $user_id;
            $record["integral"]= DriverModel::INTEGRAL_REGISTER;
            $record["user_type"]= DriverModel::USER_TYPE_USER;
            $record["type"]    = DriverModel::TYPE_IN;
            $record["operation_type"] = 1;
            $record["tag"] = "司机注册";
            IntegralModel::getInstance()->userIntegralRecordInsert($record);
            Db::commit();
            return $user_id;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }

    // 用户注册
    public function userUpdate($user_id, $param){
        Db::startTrans();
        try {
            $param["create_time"] = CURR_TIME;
            $param["update_time"] = CURR_TIME;
            $this->userEdit(["id"=>$user_id], $param);
            // 我的积分
            IntegralModel::getInstance()->userIntegralAdd(["user_id"=>$user_id, "integral"=>DriverModel::INTEGRAL_REGISTER, "user_type"=>DriverModel::USER_TYPE_USER]);
            // 积分记录
            $record["user_id"] = $user_id;
            $record["integral"]= DriverModel::INTEGRAL_REGISTER;
            $record["user_type"]= DriverModel::USER_TYPE_USER;
            $record["type"]    = DriverModel::TYPE_IN;
            $record["operation_type"] = 1;
            $record["tag"] = "司机注册";
            IntegralModel::getInstance()->userIntegralRecordInsert($record);
            Db::commit();
            return $user_id;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }

    public function wechatFind($where, $field = "*"){
        $where["is_del"] = DriverModel::USER_TYPE_USER;
        return Db::table($this->wechatTable)->field($field)->where($where)->find();
    }

    public function wxInsert($data){
        $data['type'] = DriverModel::USER_TYPE_USER;
        $data['create_time'] = CURR_TIME;
        $data['update_time'] = CURR_TIME;
        return Db::table($this->wechatTable)->insertGetId($data);
    }

    public function wechatUpdate($where, $param){
        $param['update_time'] = CURR_TIME;
        return Db::table($this->wechatTable)->where($where)->update($param);
    }

    public function userWechatFind($wechat_id, $data){
        Db::startTrans();
        try {
            $user_id = $this->userAdd($data);
            $this->wechatUpdate(["id"=>$wechat_id], ["user_id"=>$user_id]);
            Db::commit();
            return $user_id;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }

    // 添加照片
    public function addCert($data){
        return Db::table($this->certTable)->insertAll($data);
    }

    // 完善信息
    public function userPerfectInfoEdit($user_id, $data, $photo, $phone){
        Db::startTrans();
        try {
            $cart = [];
            $user_id = $this->userEdit(["id"=>$user_id], $data);
            if(!$user_id) return false;
            // 身份证 正面
            if(isset($photo["id_card"]["just"]) && $photo["id_card"]["just"]){
                $_cart["main_id"] = $user_id;
                $_cart["img"]  = $photo["id_card"]["just"];
                $_cart["type"] = DriverModel::CARD_JUST;
                $_cart["create_time"] = CURR_TIME;
                $_cart["update_time"] = CURR_TIME;
                $cart[] = $_cart;
            }
            // 身份证 反面
            if(isset($photo["id_card"]["back"]) && $photo["id_card"]["back"]){
                $_cart["main_id"] = $user_id;
                $_cart["img"]  = $photo["id_card"]["back"];
                $_cart["type"] = DriverModel::CARD_BACK;
                $_cart["create_time"] = CURR_TIME;
                $_cart["update_time"] = CURR_TIME;
                $cart[] = $_cart;
            }
            // 驾驶证
            if(isset($photo["js_cert"]) && $photo["js_cert"]){
                $_cart["main_id"] = $user_id;
                $_cart["img"]  = $photo["js_cert"];
                $_cart["type"] = DriverModel::JS_CARD;
                $_cart["create_time"] = CURR_TIME;
                $_cart["update_time"] = CURR_TIME;
                $cart[] = $_cart;
            }
            // 行驶证
            if(isset($photo["xs_cert"]) && $photo["xs_cert"]){
                $_cart["main_id"] = $user_id;
                $_cart["img"]  = $photo["xs_cert"];
                $_cart["type"] = DriverModel::XS_CARD;
                $_cart["create_time"] = CURR_TIME;
                $_cart["update_time"] = CURR_TIME;
                $cart[] = $_cart;
            }
            // 行驶证
            if(isset($photo["car"]) && $photo["car"]){
                $car = explode(",", $photo["car"]);
                foreach ($car as $key => $val) {
                    $_cart["main_id"] = $user_id;
                    $_cart["img"] = $val;
                    $_cart["type"] = DriverModel::DRIVER_CAR;
                    $_cart["create_time"] = CURR_TIME;
                    $_cart["update_time"] = CURR_TIME;
                    $cart[] = $_cart;
                }
            }
            if($cart) $this->addCert($cart);
            // 发短信
            if ($phone) {
                $response = DriverSms::auditNotice($phone);
                if ($response->Code != 'OK') {
                    return false;
                }
            }
            Db::commit();
            return $user_id;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }

    // 获取账单
    public function billList($where, $pages, $fields = '*'){
        $where['is_del'] = self::STATUS_DEL;
        return Db::table($this->bill_table)->field($fields)->where($where)->page($pages)->order('update_time desc')->select();
    }

    // 余额 查询余额
    public function balanceFind($where, $fields = '*'){
        $where["is_del"] = self::STATUS_DEL;
        return Db::table($this->balance)->field($fields)->where($where)->find();
    }

    // 余额 修改余额
    public function balanceEdit($where, $param){
        if(!isset($param["update_time"])){
            $param["update_time"] = CURR_TIME;
        }
        return Db::table($this->balance)->where($where)->update($param);
    }

    // 余额 修改余额
    public function balanceSetInc($where, $price){
        return Db::table($this->balance)->where($where)->setInc("balance", $price);
    }

    // 查询预约
    public function balanceInfoById($driver_id)
    {
        $where['user_id'] = $driver_id;
        $where['user_type'] = self::USER_TYPE_USER;
        $balance = $this->balanceFind($where, 'id, balance');
        if (!$balance) {
            $where['balance'] = "0.00";
            $where['create_time']  = CURR_TIME;
            $where['update_time']  = CURR_TIME;
            $where['transfer_date']= 0;
            $where['transfer_status'] = 0;
            $id = Db::table($this->balance)->insertGetId($where);
            $balance['id'] = (int)$id;
            $balance['balance'] = $where['balance'];
        }
        return $balance;
    }

    // 提现
    public function addBillAndTxBalance($driver_id, $balance_id, $balance_total, $price, $type, $pay_type, $tag, $status = 2)
    {
        Db::startTrans();
        try {
            $current_time = CURR_TIME;
            // 添加账单
            $bill['type'] = $type;
            $bill['tag']  = $tag;
            $bill['price'] = $price;
            $bill['status']= $status;
            $bill['type_status']= 1; // 提现
            $bill['balance'] = $balance_total;
            $bill['user_type']= self::USER_TYPE_USER;
            $bill['driver_id']= $driver_id;
            $bill['date']  = strtotime(CURR_DATE);
            $bill['create_time'] = $current_time;
            $bill['update_time'] = $current_time;
            $id = Db::table($this->bill_table)->insertGetId($bill);
            if(!$id) return false;
            //$balance = $this->balanceFind(['user_id'=>$driver_id, 'user_type'=>self::USER_TYPE_USER], 'balance')['balance'];
            // 提现订单
            $billw['bill_id'] = $id;
            $billw['code'] = OrderLogic::getInstance()->makeCode();
            $billw['type'] = $pay_type;
            $billw['create_time'] = $current_time;
            $billw['update_time'] = $current_time;
            Db::table($this->billWithdraw)->insert($billw);

            // 修改账号余额
            $aBalance['balance']= $balance_total;
            $aBalance['update_time']= $current_time;
            Db::table($this->balance)->where(['id' => $balance_id])->update($aBalance);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
        return true;
    }

    // 修改 账户余额
    public function balanceUp($where, $param) {
        Db::startTrans();
        try {
            // 账号增加金额


            $this->rechargeOrdeEdit(["id"=>$order["id"]], ["status"=>2]);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
        return true;
    }

    // 司机支付充值回调
    public function payDriverRechargeSuccess($order, $pay_type){
        Db::startTrans();
        try {
            // 账号增加金额
            $balanceId = $this->balanceFind(["user_id"=>$order["user_id"], "user_type"=>self::USER_TYPE_USER], "id")["id"] ?: "";
            if ($balanceId) {
                $this->balanceSetInc(["user_id"=>$order["user_id"], "user_type"=>self::USER_TYPE_USER], $order["price"]);
            } else { // 添加账单
                $data["user_id"] = $order["user_id"];
                $data["user_type"] = self::USER_TYPE_USER;
                $data["balance"] = $order["price"];
                $data["create_time"] = CURR_TIME;
                $data["update_time"] = CURR_TIME;
                Db::table($this->balance)->insert($data);
            }
            $this->rechargeOrdeEdit(["id"=>$order["id"]], ["status"=>2]);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
        return true;
    }

}