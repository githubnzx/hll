<?php

namespace app\admin\logic;
use think\Exception;
use think\exception\HttpException;
use think\Request;
use think\Loader;
use think\log;

class OrderLogic extends BaseLogic
{
    public function makeCode()
    {
        return date("YmdHis") . rand(100000, 999999);
    }

    public function handleStatus($status){
        switch ($status){
            case 0:
                return "待支付"; break;
            case 1:
                return "待支付"; break;
            case 2:
                return "已支付"; break;
            case 3:
                return "已取消"; break;
            default:
                return "未知状态"; break;
        }
    }

    // 提现
    public function alipayTransfer($order_no, $price, $desc)
    {
        $price = 0.01;

        try {
            Loader::import('alipay.DriverAlipay');
            $alipay = new \Alipay();
            return $alipay->transfer($order_no, $price, $desc);
        } catch (\Exception $e) {
            Log::error('支付宝提现失败:' . $order_no . '=>' . $e->getMessage());
            return false;
        }
    }
}