<?php
namespace app\driver\controller;
use app\common\logic\MsgLogic;
use app\driver\model\DepositMode;
use app\driver\logic\MsgLogic as DriverMsgLogic;
use app\driver\logic\DriverLogic;
use app\driver\logic\OrderLogic;
use app\common\logic\PageLogic;
use app\common\push\Push;
use app\driver\model\DriverModel;
use think\Cache;
use think\Config;


class Deposit extends Base
{
    public function pay(){
        $user_id = DriverLogic::getInstance()->checkToken();
        $price   = $this->request->param('price/f', "500");
        $pay_type = $this->request->param('pay_type/d', 0); // 1微信 2支付宝
        if(!$price || !$pay_type) return error_out('', MsgLogic::PARAM_MSG);
        $order['code']   = OrderLogic::getInstance()->makeCode();
        $order['status'] = 1;
        $order['price']  = $price;
        $order['pay_type'] = $pay_type;
        $order['user_type']= DepositMode::USER_TYPE_USER;
        $order['operate_type']= DepositMode::OPERATE_TYPE_JYJ;
        $order_id = DepositMode::getInstance()->depositOrderAddGetId($order);
        if($order_id === false) return error_out('', MsgLogic::SERVER_EXCEPTION);
        if ($pay_type === 1) { // 微信支付
            $data["wxData"] = OrderLogic::getInstance()->payWx($order['code'], $order['price'], url('user/pay/notifyWxDepositPay', '', true, true), "亟亟城运司机端交付押金");//亟亟城运会员购买
        } else {  // 支付宝支付
            $data['zfbData'] = OrderLogic::getInstance()->payZfb($order['code'], $order['price'], url('user/pay/notifyZfbDepositPay', '', true, true), "亟亟城运司机端交付押金");
        }
        return success_out($data);
    }




    // 退款押金
    public function retreat(){
        $coach_id  = DriverModel::getInstance()->checkToken();
        $userInfo = DriverModel::getInstance()->userFind(["id"=>$coach_id], "name, openid, deposit_status, deposit_price, deposit_pay_type deposit_number");
        if(!$userInfo) return error_out("", "退款失败");
        if(!$userInfo["openid"]) return error_out("", "您尚未绑定微信");
        if($userInfo["deposit_status"] === 0) return error_out("", "您还没有缴纳押金");
        // 退款数据
        $order["user_id"]  = $coach_id;
        $order["code"]     = OrderLogic::getInstance()->makeCode();;
        $order["user_type"]= DriverModel::USER_TYPE_USER;
        $order["price"]    = $userInfo["deposit_price"];
        $order["pay_type"] = $userInfo["deposit_pay_type"];
        $order["status"]   = 1;
        $order["operate_type"] = DriverModel::RETREAT_OPERATE_TYPE;
        $order_id = DepositMode::getInstance()->retreat($order, $userInfo["openid"]);
        if ($order_id === false) return error_out('', MsgLogic::SERVER_EXCEPTION);
        return success_out("", "48小时内退回你的支付账号中，请注意查收");
    }
}
