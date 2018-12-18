<?php

namespace app\driver\logic;

use app\common\config\WxPayUserConfig;
use app\user\model\OrderModel;
use app\common\config\WxPayDriverConfig;
use app\driver\model\DriverModel;
use app\user\model\TasteModel;
use app\common\sms\UserSms;
use app\common\logic\PayLogic;
use think\exception\HttpException;
use think\Loader;
use think\Log;

/**
 * Created by PhpStorm.
 * User: nzx
 * Date: 2018/03/19
 * Time: 13:00
 */
class OrderLogic extends BaseLogic
{

    public function payWx($code, $price, $notifyUrl, $body = "	亟亟城运支付")
    {
        $price = PayLogic::getInstance()->handlePayPrice($price);
        $times = CURR_TIME;
        $time_start = date("YmdHis", $times);
        $time_expire= date("YmdHis", $times + 90);
        Loader::import('wxpay.lib.WxPay#Api');
        $inputObj = new \WxPayUnifiedOrder();
        $inputObj->SetOut_trade_no($code);
        $inputObj->SetBody($body);
        $inputObj->SetNotify_url($notifyUrl);
        $inputObj->SetTotal_fee(intval($price * 100));
        $inputObj->SetTrade_type("APP");
        $inputObj->SetTime_start($time_start);
        $inputObj->SetTime_expire($time_expire);
        $config = new WxPayUserConfig();
        $order = \WxPayApi::unifiedOrder($config, $inputObj);
        if ($order['return_code'] != 'SUCCESS' || $order['result_code'] != 'SUCCESS') {
            return false;
        }
        //$inputObj->values = [];
        $data['appid'] = $order['appid'];
        $data['partnerid'] = $order['mch_id'];
        $data['prepayid'] = $order['prepay_id'];
        $data['packageValue'] = "Sign=WXPay";
        $data['noncestr'] = \WxPayApi::getNonceStr();
        $data['timestamp'] = (string)time();
        //$inputObj->values = $data;
        $data['sign'] = $inputObj->SetSign($config);
        return $data;
    }

    public function payZfb($code, $price, $notifyUrl, $body = "亟亟城运司机支付")
    {
        $price = PayLogic::getInstance()->handlePayPrice($price);
        Loader::import('alipay.DriverAlipay');
        $alipay = new \Alipay();
        $param['body'] = $body;
        $param['subject'] = '亟亟城运';
        $param['out_trade_no'] = $code;
        $param['total_fee'] = $price;
        $param['notify_url'] = $notifyUrl;
        return $alipay->orderString($param);
    }

    public function makeCode()
    {
        return date("YmdHis") . rand(100000, 999999);
    }

    public function makeContractCode($order_id)
    {
        return 'DX-APP-' . date('Y') . '-' . sprintf("%010d", $order_id);
    }

}