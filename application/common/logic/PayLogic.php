<?php
/**
 * Created by PhpStorm.
 * User: wangfeng
 * Date: 2018/1/31
 * Time: 下午12:11
 */

namespace app\common\logic;


use app\common\config\CourseConfig;

class PayLogic extends BaseLogic
{

    private $defaultPayPrice = 0.01;

    // 处理支付价格
    public function handlePayPrice($price)
    {
        $default_pay_price = config("default_pay_price") ?: false;
        if ($default_pay_price === false) {
            if(bccomp($price, 0.01, 2) === -1){
                throw new HttpException(200, "支付价格有误");
            }
            return $price;
        } else {
            return $this->defaultPayPrice;
        }
    }

}