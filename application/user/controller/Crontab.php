<?php

namespace app\user\controller;

use app\user\model\IntegralModel;
use app\common\push\Push;
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


}