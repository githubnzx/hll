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
    private $defaultTransferZfbPrice = 0.1;
    private $defaultTransferWxPrice = 1;

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

    // 微信提现 最低1元
    public function handleTransferWxPayPrice($price)
    {
        $default_pay_price = config("default_pay_price") ?: false;
        if ($default_pay_price === false) {
            if(bccomp($price, 1, 2) === -1){
                throw new HttpException(200, "支付价格有误");
            }
            return $price;
        } else {
            return $this->defaultTransferWxPrice;
        }
    }
    // 支付宝提现 最低0.1元
    public function handleTransferZfbPayPrice($price)
    {
        $default_pay_price = config("default_pay_price") ?: false;
        if ($default_pay_price === false) {
            if(bccomp($price, 0.1, 2) === -1){
                throw new HttpException(200, "支付价格有误");
            }
            return $price;
        } else {
            return $this->defaultTransferZfbPrice;
        }
    }

}