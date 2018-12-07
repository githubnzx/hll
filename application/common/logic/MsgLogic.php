<?php
/**
 * Created by PhpStorm.
 * User: wangfeng
 * Date: 2018/1/31
 * Time: 下午12:11
 */

namespace app\common\logic;


use app\common\config\CourseConfig;

class MsgLogic extends BaseLogic
{
    const SERVER_EXCEPTION = "服务器异常";
    const ADD_SUCCESS      = "添加成功";
    const EDIT_SUCCESS     = "修改成功";
    const DEL_SUCCESS      = "删除成功";
    const REG_SUCCESS      = "注册成功";
    const PARAM_MSG        = "参数错误";
    const SUCCESS          = "成功";

    const INTEGRAL_SURPLUS_NUMBER = "该商品已兑完";
    const INTEGRAL_CONVERTIBILITY = "该商品已兑换";
    const INTEGRAL_DH_SUCCESS     = "兑换成功";
    const INTEGRAL_RECEIPT_MSG    = "订单不可确认";

}