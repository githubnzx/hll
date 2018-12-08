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

    public function refundWx($code, $price)
    {
        $price = PayLogic::getInstance()->handlePayPrice($price);
        Loader::import('wxpay.user.lib.WxPay#Api');
        $inputObj = new \WxPayRefund();
        $inputObj->SetOut_trade_no($code);
        $inputObj->SetOut_refund_no($code);
        $inputObj->SetTotal_fee(intval($price * 100));
        $inputObj->SetRefund_fee(intval($price * 100));
        $order = \WxPayApi::refund($inputObj);
        if ($order['return_code'] != 'SUCCESS' || $order['result_code'] != 'SUCCESS') {
            Log::error('微信退款失败:' . $code . '=>' . $order['err_code_des']);
        }
        return $order;
    }

    // 微信提现
    public function transferWx($code, $openid, $price, $check_name = 'FORCE_CHECK', $user_name = '')
    {
        $price = PayLogic::getInstance()->handlePayPrice($price);
        Loader::import('wxpay.user.lib.WxPay#Api');
        $inputObj = new \WxTransOrder();
        $inputObj->SetPartner_trade_no($code);
        $inputObj->SetOpen_id($openid);
        $inputObj->SetCheck_name($check_name);
        $inputObj->SetRe_user_name($user_name);
        $inputObj->SetAmount(intval($price * 100));
        $inputObj->SetDesc('提现');
        $order = \WxPayApi::transfer($inputObj);
        if ($order['return_code'] != 'SUCCESS' || $order['result_code'] != 'SUCCESS') {
            Log::error('微信提现失败:' . $code . '=>' . $order['err_code_des']);
            return false;
        }
        return true;
    }

    // 支付宝退款
    public function refundZfb($code, $price, $desc)
    {
        $price = PayLogic::getInstance()->handlePayPrice($price);
        try {
            Loader::import('alipay.Alipay');
            $alipay = new \Alipay();
            return $alipay->refund($code, $price, $desc);
        } catch (\Exception $e) {
            Log::error('支付宝退款失败:' . $code . '=>' . $e->getMessage());
            return false;
        }
    }

    public function payZfb($code, $price, $notifyUrl, $body = "亟亟城运司机支付")
    {
        $price = PayLogic::getInstance()->handlePayPrice($price);
        Loader::import('alipay.driver.Alipay');
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