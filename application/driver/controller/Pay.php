<?php
namespace app\driver\controller;

use app\driver\model\DepositMode;
use app\driver\model\DriverModel;
use app\user\logic\OrderLogic;
use app\driver\model\OrderModel;
use app\driver\model\MemberModel;
use think\Loader;


class Pay extends Base
{
    // 微信
    public function notifyWx()
    {
        $result = $this->wechatNotifyHandel(function ($code) {
            return $this->updateOrder($code, OrderModel::ORDER_PAY_WX);
        });
        exit($result);
    }



    private function wechatNotifyHandel($callback)
    {
        Loader::import('wxpay.user.lib.WxPay#Api');
        $notifyReply = new \WxPayNotifyReply();
        $msg = "OK";
        //验证签名
        $result = \WxpayApi::notify(function ($data) use ($callback) {
            if ($data['result_code'] == 'SUCCESS' && $data['return_code'] == 'SUCCESS') {
                return call_user_func($callback, $data['out_trade_no']);
            } else {
                return false;
            }
        }, $msg);
        if ($result == false) {
            $notifyReply->SetReturn_code("FAIL");
        } else {
            $notifyReply->SetReturn_code("SUCCESS");
        }
        $notifyReply->SetReturn_msg($msg);
        return $notifyReply->ToXml();
    }
    // 支付宝
    public function notifyZfb()
    {
        $result = $this->zfbNotifyHandel(function ($code) {
            return $this->updateOrder($code, OrderModel::ORDER_PAY_ZFB);
        });
        exit($result);
    }

    //  支付宝 回调签名验证
    private function zfbNotifyHandel($callback)
    {
        Loader::import('alipay.Alipay');
        $alipay = new \Alipay();
        $data = input('post.');
        $is_true = $alipay->checkSign($data);
        if (!$is_true) {
            return '签名验证失败';
        }
        $msg = "fail";
        //交易状态
        if ($data['trade_status'] == 'TRADE_FINISHED' || $data['trade_status'] == 'TRADE_SUCCESS') {
            $result = call_user_func($callback, $data['out_trade_no']);
            if ($result) {
                $msg = "success";
            }
        }
        return $msg;
    }

    public function updateOrder($code, $pay_type)
    {
        $where['o.code'] = $code;
        $orderModel = new OrderModel();
        $fields = 'o.id, o.field_id, o.status, o.coach_id, o.pay_user_id, o.user_id, o.price, o.user_price, o.experien_status, o.schedule_id, o.field_type_id, o.course_id, o.type_id, od.date, od.time_nodes';
        $order =  OrderModel::getInstance()->getOrderOne($where, $fields); //getOrderList getOrderByCode
        if ($order['status'] != 1) { //不是待支付状态
            return false;
        }
        // 处理账户
        $result = $orderModel->paySuccess($order, $pay_type);
        return $result;
    }
    /* *********************************** 司机购买会员 *********************************************/

    // 微信 司机购买会员
    public function notifyWxMemberPay()
    {
        $result = $this->wechatNotifyHandel(function ($code) {
            return $this->updateMemberOrder($code, OrderModel::ORDER_PAY_TYPE_WX);
        });
        exit($result);
    }

    // 司机会员购买回调
    public function updateMemberOrder($code, $pay_type)
    {
        $order =  MemberModel::getInstance()->memberOrderFind(["code"=>$code]);
        if ($order['status'] != 1) return false;
        // 处理账户
        $result = MemberModel::getInstance()->payDriverMemberSuccess($order, $pay_type);
        return $result;
    }

    // 支付宝 司机会员购买
    public function notifyZfbMemberPay()
    {
        $result = $this->zfbNotifyHandel(function ($code) {
            return $this->updateMemberOrder($code, OrderModel::ORDER_PAY_ZFB);
        });
        exit($result);
    }
    /* ********************************************************************************************/

    /* *********************************** 司机机会员充值 *********************************************/

    // 微信 司机会员充值
    public function notifyWxRecharge()
    {
        $result = $this->wechatNotifyHandel(function ($code) {
            return $this->updateRechargeOrder($code, OrderModel::ORDER_PAY_WX);
        });
        exit($result);
    }

    // 司机会员充值回调
    public function updateRechargeOrder($code, $pay_type)
    {
        $order =  DriverModel::getInstance()->rechargeOrderFind(["code"=>$code]);
        if ($order['status'] != 1) return false;
        // 处理账户
        $result = DriverModel::getInstance()->payDriverRechargeSuccess($order, $pay_type);
        return $result;
    }

    // 支付宝 司机会员充值
    public function notifyZfbRecharge()
    {
        $result = $this->zfbNotifyHandel(function ($code) {
            return $this->updateRechargeOrder($code, OrderModel::ORDER_PAY_ZFB);
        });
        exit($result);
    }

    /* ********************************************************************************************/


    /* *********************************** 司机交付押金 *********************************************/

    // 微信 司机交付押金 默认价格 500 元
    public function notifyWxDepositPay()
    {
        $result = $this->wechatNotifyHandel(function ($code) {
            return $this->updateDepositOrder($code, OrderModel::ORDER_PAY_WX);
        });
        exit($result);
    }
    // 支付宝 司机交付押金回调 默认价格 500 元
    public function updateDepositOrder($code, $pay_type){
        $order =  DepositMode::getInstance()->depositOrderFind(["code"=>$code]);
        if ($order['status'] != 1) return false;
        // 处理账户
        $result = DepositMode::getInstance()->depositOrderEdit($order, $pay_type);
        return $result;
    }
    // 支付宝 司机交付押金 默认价格 500 元
    public function notifyZfbDepositPay()
    {
        $result = $this->zfbNotifyHandel(function ($code) {
            return $this->updateDepositOrder($code, OrderModel::ORDER_PAY_ZFB);
        });
        exit($result);
    }

    /* ********************************************************************************************/







}
