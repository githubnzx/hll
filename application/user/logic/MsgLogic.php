<?php

namespace app\user\logic;
use think\exception\HttpException;
use think\Cache;
use think\Request;
use think\Db;

class MsgLogic extends BaseLogic
{


    const ADVISE_MSG_TITLE   = "请输入问题的标题描述";
    const ADVISE_MSG_CONTENT = "请输入问题的详细描述";
    const ADVISE_MSG_CONTENT_RANGE = "请输入详细描述有效的范围";
    const ADVISE_MSG_SUCCESS = "谢谢您的建议，我们将持续为你改进";
    const ADVISE_MSG_EXCEED  = "网络拥挤，请稍后再试";
    const USER_PHONE_NOT_EXTSIS  = "用户手机号不存在";

    // 订单
    const ORDER_USER_NAME    = "用户名格式错误";
    const ORDER_IS_EXISTS    = "有未完成订单，不可预约";
    const ORDER_IS_RECEIVABLE= "预约必填";
    const ORDER_NOT_EXISTS   = "订单有误";
    const ORDER_BERESERVED_EXISTS   = "订单已被预约";
    const ORDER_UPPER_LIMIT  = "抢单已达上限";

    // 货车
    const TRUCK_IS_EXISTS    = "货车不存在";
    // 评论
    const EVALUATE_CONTENT   = "评价最多输入300字";
    // 熟人
    const FRIEND_PHONE_EXISTS= "该手机号已存在";




    const COACH_USER_TYPE = 1;




}