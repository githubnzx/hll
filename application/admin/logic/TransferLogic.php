<?php

namespace app\admin\logic;
use app\common\config\WxPayUserConfig;
use app\common\logic\PayLogic;
use think\Exception;
use think\exception\HttpException;
use think\Request;
use think\Loader;
use think\log;

/**
 * Created by PhpStorm.
 * User: nzx
 * Date: 2018/04/21
 * Time: 上午11:33
 */
class TransferLogic extends BaseLogic
{
    public function makeCode()
    {
        return date("YmdHis") . rand(100000, 999999);
    }

    public function transferWx_old($code, $openid, $price, $user_type, $user_name = '', $check_name = 'NO_CHECK')
    {
        $price = 1;
        Loader::import('wxpay.lib.WxPay#Api');
        $inputObj = new \WxTransOrder();
        $inputObj->SetPartner_trade_no($code);
        $inputObj->SetOpen_id($openid);
        $inputObj->SetCheck_name($check_name);
        $inputObj->SetRe_user_name($user_name);
        $inputObj->SetAmount(intval($price * 100));
        $inputObj->SetDesc('asdfasdf'); //var_dump($inputObj);die;
        $config = new WxPayUserConfig();
        var_dump($config);die;
        $order = \WxPayApi::transfer($config, $inputObj);
        var_dump($order);die;
        //var_dump($order);die;
//        if ($order['return_code'] != 'SUCCESS' || $order['result_code'] != 'SUCCESS') {
//            Log::error('微信提现失败:' . $code . '=>' . $order['err_code_des']);
//            //return false;
//        }
        //return true;
        return $order;
    }

    // 企业转账到零钱
    
    public function transferWx($code, $openid, $price, $user_name = '', $check_name = 'NO_CHECK')
    {
        $price = 1;//PayLogic::getInstance()->handlePayPrice($price);
        Loader::import('wxpay.lib.WxPay#Api');
        $inputObj = new \WxTransOrder();
        $inputObj->SetPartner_trade_no($code);
        $inputObj->SetOpen_id($openid);
        $inputObj->SetCheck_name($check_name);
        $inputObj->SetRe_user_name($user_name);
        $inputObj->SetAmount(intval($price * 100));
        $inputObj->SetDesc('tixian'); //var_dump($inputObj);die;
        $config = new WxPayUserConfig();
        $order = \WxPayApi::transfer($config, $inputObj);
        var_dump($order);die;
        if ($order['return_code'] != 'SUCCESS' || $order['result_code'] != 'SUCCESS') {
            Log::error('微信提现失败:' . $code . '=>' . $order['err_code_des']);
            //return false;
        }
        //return true;
        return $order;
    }


    // 支付宝退款
    public function refundZfb($code, $price, $desc)
    {
        $price = 0.01;
        try {
            Loader::import('alipay.DriverAlipay');
            $alipay = new \DriverAlipay();
            return $alipay->refund($code, $price, $desc);
        } catch (\Exception $e) {
            Log::error('支付宝退款失败:' . $code . '=>' . $e->getMessage());
            return false;
        }
    }

    // 单笔转账到支付宝账户接口
    public function transferZfb($code, $price, $desc)
    {
        $price = 0.01;
        try {
            Loader::import('alipay.DriverAlipay');
            $alipay = new \DriverAlipay();
            return $alipay->transfer($code, $price, $desc);
        } catch (\Exception $e) {
            Log::error('支付宝退款失败:' . $code . '=>' . $e->getMessage());
            return false;
        }
    }
}