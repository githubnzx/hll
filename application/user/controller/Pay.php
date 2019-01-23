<?php
namespace app\user\controller;

use app\user\model\DepositMode;
use app\user\model\UsersModel;
use app\user\logic\OrderLogic;
use app\user\model\OrderModel;
use app\user\model\MemberModel;
use app\user\model\DepositModel;
use app\common\config\WxPayUserConfig;
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

    // 支付宝
    public function notifyZfb()
    {
        $result = $this->zfbNotifyHandel(function ($code) {
            return $this->updateOrder($code, OrderModel::ORDER_PAY_ZFB);
        });
        exit($result);
    }

    // 用户订单 回调
    public function updateOrder($code, $pay_type)
    {
        $order =  OrderModel::getInstance()->orderFind(["code"=>$code]);
        if ($order['status'] != 1) return false;
        // 处理账户
        $result = OrderModel::getInstance()->orderSuccess($order, $pay_type);
        return $result;
    }

    /**************************************  用户充值 *******************************************************/
    // 微信 用户充值
    public function notifyWxRecharge()
    {
        $result = $this->wechatNotifyHandel(function ($code) {
            return $this->updateRechargeOrder($code, OrderModel::ORDER_PAY_WX);
        });
        exit($result);
    }
    // 用户充值 回调
    public function updateRechargeOrder($code, $pay_type)
    {
        $order =  UsersModel::getInstance()->rechargeOrderFind(["code"=>$code]);
        if ($order['status'] != 1) return false;
        // 处理账户
        $result = UsersModel::getInstance()->payDriverRechargeSuccess($order, $pay_type);
        return $result;
    }
    // 支付宝 用户充值
    public function notifyZfbRecharge()
    {
        $result = $this->zfbNotifyHandel(function ($code) {
            return $this->updateRechargeOrder($code, OrderModel::ORDER_PAY_ZFB);
        });
        exit($result);
    }
    /****************************************************************************************************/

//    // 微信-押金
//    public function notifyWxDeposit()
//    {
//        $result = $this->wechatNotifyHandel(function ($code) {
//            return $this->updateOrderDeposit($code, OrderModel::ORDER_PAY_WX);
//        });
//        exit($result);
//    }

//    // 押金
//    public function updateOrderDeposit($code, $pay_type)
//    {
//        $order =  DepositModel::getInstance()->depositOrderFind(["code"=>$code]);
//        if ($order['status'] != 1) return false;
//        // 处理账户
//        $result = DepositModel::getInstance()->orderPaySuccess($order, $pay_type);
//        return $result;
//    }

    // 微信 回调签名验证
    private function wechatNotifyHandel($callback)
    {
        Loader::import('wxpay.lib.WxPay#Api');
        $notifyReply = new \WxPayNotifyReply();
        $msg = "OK";
        //验证签名
        $config = new WxPayUserConfig();
        $result = \WxpayApi::notify($config, function ($data) use ($callback) {
            $dataValues = (array) $data->values;
            if ($dataValues['result_code'] == 'SUCCESS' && $dataValues['return_code'] == 'SUCCESS') {
                return call_user_func($callback, $dataValues['out_trade_no']);
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






//    // 司机会员充值回调(支付宝)
//    public function updateZfbRechargeOrder($code, $pay_type)
//    {
//        $order =  UsersModel::getInstance()->rechargeOrderFind(["code"=>$code]);
//        if ($order['status'] != 1) return false;
//        // 处理账户
//        $result = UsersModel::getInstance()->payDriverRechargeSuccess($order, $pay_type);
//        return $result;
//    }

    /*public function notifySalaryZfb()
    {
        $result = $this->zfbNotifyHandel(function ($code) {
            return $this->updateSalary($code, OrderModel::ORDER_PAY_TYPE_ZFB);
        });
        exit($result);
    }*/

    // 支付宝 回调签名验证
    private function zfbNotifyHandel($callback)
    {
        Loader::import('alipay.UserAlipay');
        $alipay = new \UserAlipay();
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



//    public function updateOrder($code, $pay_type)
//    {
//        $where['o.code'] = $code;
//        $orderModel = new OrderModel();
//        $fields = 'o.id, o.field_id, o.status, o.coach_id, o.pay_user_id, o.user_id, o.price, o.user_price, o.experien_status, o.schedule_id, o.field_type_id, o.course_id, o.type_id, od.date, od.time_nodes';
//        $order =  OrderModel::getInstance()->getOrderOne($where, $fields); //getOrderList getOrderByCode
//        if ($order['status'] != 1) { //不是待支付状态
//            return false;
//        }
//        // 处理账户
//        $result = $orderModel->paySuccess($order, $pay_type);
//        return $result;
//    }

    public function notifyMemberWx()
    {
        $result = $this->wechatNotifyHandel(function ($code) {
            return $this->updateMemberOrder($code, OrderModel::ORDER_PAY_TYPE_WX);
        });
        exit($result);
    }

    // 司机会员回调
    public function updateMemberOrder($code, $pay_type)
    {
        $order =  MemberModel::getInstance()->memberOrderFind(["code"=>$code]);
        if ($order['status'] != 1) return false;
        // 处理账户
        $result = MemberModel::getInstance()->payDriverMemberSuccess($order, $pay_type);
        return $result;
    }



    // 会员卡场地微信支付
    public function notifyMyMemberWx()
    {
        $result = $this->wechatNotifyHandel(function ($code) {
            return $this->updateMyMemberOrder($code, OrderModel::ORDER_PAY_TYPE_WX);
        });
        exit($result);
    }

    public function updateMyMemberOrder($code, $pay_type)
    {
        ///$order = OrderModel::getInstance()->getOrderByCode($code);
        $fields = 'o.id, o.field_id, o.status, o.coach_id, o.pay_user_id, o.order_time, o.field_price, o.field_pay_type, o.pay_type, o.experien_status, o.total_field_price, o.member_user_id, o.user_id, o.price, o.schedule_id, o.field_type_id, o.course_id, o.type_id, od.date, od.time_nodes';
        $order =  OrderModel::getInstance()->getOrderOne(["code"=>$code], $fields); //getOrderList getOrderByCode
        if ($order['status'] != 1) return false;
        // 获取订单时间
        $order_date = OrderModel::getInstance()->getOrderDateList(["order_id"=>$order["id"]], "id, date, time_nodes");
        // 获取会员价格
        $member_price = MemberModel::getInstance()->memberUserPriceList(["member_user_id"=>$order["member_user_id"], "surplus_number"=> ["GT", 0]], "id, surplus_number, avg_price", "surplus_number asc");
        if(!$order_date || !$member_price) return false;
        // 私教 体验课 去掉一小时
        if($order["type_id"] === 2 && $order["experien_status"]){
            $time_nodes_arr = explode(",", $order_date[0]["time_nodes"]);
            $time_nodes_str = implode(",", array_slice($time_nodes_arr, 2));
            $order_date[0]["time_nodes"] = $time_nodes_str;
        }
        $order_date_list = OrderLogic::getInstance()->memberAvgPirce($order_date, $member_price);
        $result = OrderModel::getInstance()->upMyMemberAndOrder($order, $order_date_list, $pay_type);
        return $result;
    }

    // 尊享购买 notifyDiscountWx
    public function notifyDiscountWx()
    {
        $result = $this->wechatNotifyHandel(function ($code) {
            return $this->updateDiscountMOrder($code, OrderModel::ORDER_PAY_TYPE_WX);
        });
        exit($result);
    }
    // 尊享会员
    public function updateDiscountMOrder($code, $pay_type)
    {
        $order =  MemberModel::getInstance()->memberOrderFind(["code"=>$code]);
        if ($order['status'] != 1) return false;
        // 处理账户
        $result = MemberModel::getInstance()->discountPaySuccess($order, $pay_type);
        return $result;
    }







}
