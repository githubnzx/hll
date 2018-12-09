<?php
namespace app\user\controller;
use app\common\logic\MsgLogic;
use app\user\model\DepositMode;
use app\user\model\IntegralModel;
use app\user\model\UsersModel;
use app\user\logic\UserLogic;
use app\user\logic\OrderLogic;
use app\user\logic\MsgLogic as UserMsgLogic;
use app\user\model\MyModel;
use app\common\logic\PageLogic;
use app\common\push\Push;
use think\Cache;
use think\Config;


class Deposit extends Base
{
    public function pay(){
        $user_id = UserLogic::getInstance()->checkToken();
        $price   = $this->request->param('price/s', "500");
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
            $data["wxData"] = OrderLogic::getInstance()->payWx($order['code'], $order['price'], url('user/pay/notifyWxDeposit', '', true, true), "APP");//亟亟城运会员购买
        } else {  // 支付宝支付
            $data['zfbData'] = OrderLogic::getInstance()->payZfb($order['code'], $order['price'], url('user/pay/notifyZfb', '', true, true));
        }
        return success_out($data);
    }



}