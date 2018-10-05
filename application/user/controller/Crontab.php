<?php

namespace app\user\controller;

use app\user\logic\OrderLogic;
use app\user\model\IntegralModel;
use app\common\push\Push;
use app\user\model\OrderModel;
use im\Easemob;
use think\Log;

class Crontab extends Base
{
    /**
     * 立即兑换商品15日自动 确认收货
     * *  22  *  *  * root /usr/local/php/bin/php  /home/wwwroot/public/user.php crontab/CheckEndOrderScan
     */
    public function CheckConfirmReceipt(){
        set_time_limit(0);
        $data['date']   = currZeroDateToTime() - (3600 * 24 * 15);
        $data['status'] = 1;
        IntegralModel::getInstance()->integralOrderEdit($data, ["status" => 2]);
        die('OK');
    }

    /**
     * 预约司机
     * *  *  *  *  * root /usr/local/php/bin/php  /home/wwwroot/public/user.php crontab/CheckEndOrderScan
     */
    public function CheckOrder(){
        set_time_limit(0);
        $data['order_time'] = strtotime(date("Y-m-d H:i"));
        $data['is_place_order'] = 1;
        $orderLset = OrderModel::getInstance()->orderSelect($data, "id, driver_ids");
        foreach ($orderLset as $key => $value) {
            OrderModel::getInstance()->placeOrderEdit($value["id"], $value["driver_ids"]);
        }
        die('OK');
    }


}