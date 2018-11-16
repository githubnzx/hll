<?php

namespace app\user\logic;

use app\common\config\DriverConfig;
use app\user\model\OrderModel;
use app\user\model\UsersModel;
use app\common\config\WxPayUserConfig;
use app\common\sms\UserSms;
use think\exception\HttpException;
use think\Loader;
use think\Log;
use WxPayApi;

/**
 * Created by PhpStorm.
 * User: nzx
 * Date: 2018/03/19
 * Time: 13:00
 */
class OrderLogic extends BaseLogic
{

    public function payWx($code, $price, $notifyUrl)
    {
        $price = 0.01;
        $times = CURR_TIME;
        //Loader::import('wxpay.user.lib.WxPay#Api');
        $inputObj = new \WxPayUnifiedOrder();
        $inputObj->SetOut_trade_no($code);
        $inputObj->SetBody('hll');
        $inputObj->SetNotify_url($notifyUrl);
        $inputObj->SetTotal_fee(intval($price * 100));
        $inputObj->SetTrade_type("APP");
        $config = new WxPayUserConfig();
        $order = WxPayApi::unifiedOrder($config, $inputObj);
        var_dump($order);die;
        if ($order['return_code'] != 'SUCCESS' || $order['result_codes'] != 'SUCCESS') {
            return false;
        }
        $inputObj->values = [];
        $data['appid'] = $order['appid'];
        $data['partnerid'] = $order['mch_id'];
        $data['prepayid'] = $order['prepay_id'];
        $data['packageValue'] = "Sign=WXPay";
        $data['noncestr'] = \WxPayApi::getNonceStr();
        $data['timestamp'] = (string)time();
        $inputObj->values = $data;
        $data['sign'] = $inputObj->SetSign();
        return $data;
    }

    public function refundWx($code, $price)
    {
        $price = 0.01;
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

    public function transferWx($code, $openid, $price, $check_name = 'FORCE_CHECK', $user_name = '')
    {
        $price = 0.01;
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

    public function refundZfb($code, $price, $desc)
    {
        $price = 0.01;
        try {
            Loader::import('alipay.Alipay');
            $alipay = new \Alipay();
            return $alipay->refund($code, $price, $desc);
        } catch (\Exception $e) {
            Log::error('支付宝退款失败:' . $code . '=>' . $e->getMessage());
            return false;
        }
    }

    public function payZfb($code, $price, $notifyUrl)
    {
        $price = 0.01;
        Loader::import('alipay.user.Alipay');
        $alipay = new \Alipay();
        $param['body'] = '亟亟城运';
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

   /* public function actionMessage($orderList, $title, $content, $delay = false)
    {
        $current_time = time();
//        $messagesList = [];
        if ($delay) {
            $push_time = strtotime("+10 hours");
        } else {
            $push_time = strtotime("+2 seconds");
        }
        $messagesModel = new MessagsModel();
        foreach ($orderList as $k => $order) {
            $role_id = isset($order['role_id']) ? $order['role_id'] : MessagsModel::ROLE_SERVICE;

            $messages = [
                'u_id' => $order['user_id'],
                'order_id' => $order['id'],
                'role_id' => $role_id,
                'read_status' => 0,
                'status' => 1,
                'title' => $title,
                'content' => $content,
                'create_time' => $current_time,
            ];
            $messags_id = $messagesModel->insertMsg($messages);

            $userToken = getApiCache()->get('user_id:' . $order['user_id']);
            if ($userToken) {
                $extData['order_id'] = $order['id'];
                $extData['messags_id'] = $messags_id;
                $extData['role_id'] = $role_id;
                $ext = json_encode($extData);
                $pushResult = PushLogic::getInstance()->sendAll($userToken, $title, $content, $ext, $push_time, 'ACCOUNT');
            } else {
                $pushResult = false;
            }
        }
//        $messagesModel->insertMsgList($messagesList);
    }
*/
    public function makeContractCode($order_id)
    {
        return 'DX-APP-' . date('Y') . '-' . sprintf("%010d", $order_id);
    }

    // 计算价格
    public function imputedPrice($kilometers, $truck_type, $fee_price){
        $truckPrice = DriverConfig::getInstance()->truckPriceId($truck_type);
        if($kilometers <= 5) return $truckPrice["starting_price"];
        $kilometers = ceil($kilometers);
        $actualKilometer = bcsub($kilometers, 5);  // 总共里数减去5公里（起步价包含5公里）
        // 起步价 + 超出公里数总价格 = 总费用
        $actualKilometerPrice = bcadd($truckPrice["starting_price"], bcsub($actualKilometer, $truckPrice["excess_fee"]));
        $price = bcadd($actualKilometerPrice, $fee_price); // 总公里价 + 小费
        return $price;
    }

}