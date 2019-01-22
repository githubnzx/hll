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
use app\common\sms\UserSms;
use app\user\logic\UserLogic;
use app\user\logic\OrderLogic;
use im\Easemob;
use think\Db;
use think\Log;
use think\Model;

class UsersModel extends BaseModel
{
    protected $tableUser = 'users';
    protected $wechatTable = 'wechat';
    protected $balance = 'balance';
    protected $bill_table = 'bill';
    protected $billWithdraw = 'bill_withdraw';
    protected $rechargeOrder = 'recharge_order';

    const STATUS_DEL = 0;
    const USER_TYPE_USER  = 1;
    const INTEGRAL_REGISTER = 10;
    const TYPE_IN  = 1;
    const TYPE_OUT = 2;

    const STAY_PAY = 1;

    const WX_THIRD_PARTY_TYPE = 1;
    const ZFB_THIRD_PARTY_TYPE= 2;
    const QQ_THIRD_PARTY_TYPE = 3;


    public function userFind($where, $fields = '*'){
        $result = Db::table($this->tableUser)->field($fields)->where($where)->find();
        if(isset($result["icon"]) && $result["icon"]){
            $result["icon"] = handleImgPath($result["icon"]);
        }
        return $result;
    }

    //提现绑定微信
    public function wechatAuth($user_id, $access_result)
    {
        $param['access_token'] = $access_result['access_token'];
        $param['refresh_token']= $access_result['refresh_token'];
        $param['openid'] = $update['openid'] = $access_result['openid'];
        if (isset($access_result['unionid']) && $access_result['unionid']){
            $param['unionid'] = $update['unionid'] = $access_result['unionid'];
        }
        $wechat_info = $this->wechatFind(["openid"=>$access_result['openid'], "type"=>self::USER_TYPE_USER], "id, openid");
        if ($wechat_info){
            try{
                $this->wechatUpdate(["id"=>$wechat_info['id']],$param);
                $this->userEdit(["id"=>$user_id], $update);
                Db::commit();
            }catch (Exception $e){
                Db::rollback();
                return error_out('',$e->getMessage());
            }
        }else{
            try{
                $param['user_id'] = $user_id;
                $this->userEdit(["id"=>$user_id], $update);
                $this->wxInsert($param);
                Db::commit();
            }catch (Exception $e){
                Db::rollback();
                return error_out('',$e->getMessage());
            }
        }
        return true;
    }

    //提现绑定支付宝
    public function zfbAuth($user_id, $access_result)
    {
//        $param['access_token'] = $access_result['access_token'];
//        $param['refresh_token']= $access_result['refresh_token'];
        $param["nickname"] = $access_result["nick_name"];
        $param["headimgurl"] = $access_result["avatar"];
        if ($access_result["is_certified"] === "T"){
            $param["sex"] = $access_result["gender"] === "F" ? 2 : 1;
        }
        $param["unionid"] = "";
        $param['openid'] = $update['zfb_unique_id'] = $access_result['user_id'];
        $wechat_info = $this->wechatFind(["openid"=>$access_result['user_id'], "third_party_type"=>UsersModel::ZFB_THIRD_PARTY_TYPE, "type"=>self::USER_TYPE_USER], "id, openid");
        if ($wechat_info){
            try{
                $this->wechatUpdate(["id"=>$wechat_info['id']], $param);
                $this->userEdit(["id"=>$user_id], $update);
                Db::commit();
            }catch (Exception $e){
                Db::rollback();
                return error_out('',$e->getMessage());
            }
        }else{
            try{
                $param['user_id'] = $user_id;
                $param['third_party_type'] = UsersModel::ZFB_THIRD_PARTY_TYPE;
                $this->userEdit(["id"=>$user_id], $update);
                $this->wxInsert($param);
                Db::commit();
            }catch (Exception $e){
                Db::rollback();
                return error_out('',$e->getMessage());
            }
        }
        return true;
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

    public function userInsert($data){
        $data['create_time'] = CURR_TIME;
        $data['update_time'] = CURR_TIME;
        return Db::table($this->tableUser)->insertGetId($data);
    }

    // 微信登录绑定用户表中不存在的手机号 注册
    public function wechatRegisterAdd($data){
        Db::startTrans();
        try {
            $user_id = $this->userInsert($data);
            // 发送短信给用户 （默认注册密码）

            Db::commit();
            return $user_id;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
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

    // 用户注册
    public function userAdd($data){
        Db::startTrans();
        try {
            $user_id = $this->userInsert($data);
            // 我的积分
            IntegralModel::getInstance()->userIntegralAdd(["user_id"=>$user_id, "integral"=>UsersModel::INTEGRAL_REGISTER, "user_type"=>UsersModel::USER_TYPE_USER]);
            // 积分记录
            $record["user_id"] = $user_id;
            $record["integral"]= UsersModel::INTEGRAL_REGISTER;
            $record["user_type"]= UsersModel::USER_TYPE_USER;
            $record["type"]    = UsersModel::TYPE_IN;
            $record["operation_type"] = 1;
            $record["tag"] = "用户注册";
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
            IntegralModel::getInstance()->userIntegralAdd(["user_id"=>$user_id, "integral"=>UsersModel::INTEGRAL_REGISTER, "user_type"=>UsersModel::USER_TYPE_USER]);
            // 积分记录
            $record["user_id"] = $user_id;
            $record["integral"]= UsersModel::INTEGRAL_REGISTER;
            $record["user_type"]= UsersModel::USER_TYPE_USER;
            $record["type"]    = UsersModel::TYPE_IN;
            $record["operation_type"] = 1;
            $record["tag"] = "用户注册";
            IntegralModel::getInstance()->userIntegralRecordInsert($record);
            Db::commit();
            return $user_id;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }

    public function wechatFind($where, $field = "*"){
        return Db::table($this->wechatTable)->field($field)->where($where)->find();
    }

    public function wxInsert($data){
        $data['type'] = UsersModel::USER_TYPE_USER;
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
            $this->wechatUpdate(["id"=>$wechat_id], ["user_id"=>$user_id, "type"=>self::USER_TYPE_USER]);
            Db::commit();
            return $user_id;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }

    // 余额 修改余额
    public function balanceSetInc($where, $price){
        return Db::table($this->balance)->where($where)->setInc("balance", $price);
    }

    // 修改充值订单
    public function rechargeOrdeEdit($where, $param){
        if(!isset($param["update_time"])){
            $param["update_time"] = CURR_TIME;
        }
        return Db::table($this->rechargeOrder)->where($where)->update($param);
    }

    // 添加充值订单
    public function rechargeOrderInsert($data){
        $user['create_time'] = CURR_TIME;
        $user['update_time'] = CURR_TIME;
        return Db::table($this->rechargeOrder)->insertGetId($data);
    }

    // 余额 查询余额
    public function balanceFind($where, $fields = '*'){
        $where["is_del"] = self::STATUS_DEL;
        return Db::table($this->balance)->field($fields)->where($where)->find();
    }

    // 获取账单
    public function billList($where, $pages, $fields = '*'){
        $where['is_del'] = self::STATUS_DEL;
        return Db::table($this->bill_table)->field($fields)->where($where)->page($pages)->order('update_time desc')->select();
    }

    // 添加账单
    public function billAdd($user_id, $order_id, $type, $pay_type, $price, $pay_type = 1, $tag = "充值"){
        $bill["user_id"]  = $user_id;
        $bill["order_id"] = $order_id;
        $bill["type"]     = $type;
        $bill["pay_type"] = $pay_type;  // 1微信 2支付宝 3余额
        $bill["type_status"] = $pay_type;  // 支出类型 1 体现 2 充值
        $bill["user_type"]= self::USER_TYPE_USER;
        $bill["tag"]    = $tag;
        $bill["price"]  = $price;
        $bill["status"] = 2;
        $bill["date"]   = CURR_TIME;
        $bill["create_time"] = CURR_TIME;
        $bill["update_time"] = CURR_TIME;
        return Db::table($this->bill_table)->insert($bill);
    }

    // 用户支付充值回调
    public function payDriverRechargeSuccess($order, $pay_type){
        Db::startTrans();
        try {
            // 账号增加金额
            //$this->balanceSetInc(["user_id"=>$order["user_id"], "user_type"=>self::USER_TYPE_USER], $order["price"]);
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
            // 添加账单
            $this->billAdd($order["user_id"], $order["id"], 1, $pay_type, $order["price"], 2);

            $this->rechargeOrdeEdit(["id"=>$order["id"]], ["status"=>2]);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
        return true;
    }

    // 查询充值订单
    public function rechargeOrderFind($where, $field = "*"){
        $where["is_del"] = self::STATUS_DEL;
        return Db::table($this->rechargeOrder)->field($field)->where($where)->find();
    }







}