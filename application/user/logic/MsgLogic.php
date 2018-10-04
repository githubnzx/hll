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
    // 订单
    const ORDER_USER_NAME    = "用户名格式错误";
    const ORDER_IS_EXISTS    = "有未完成订单，不可预约";
    const ORDER_IS_RECEIVABLE= "预约必填";
    // 货车不存在
    const TRUCK_IS_EXISTS    = "货车不存在";



    const COACH_USER_TYPE = 1;




}