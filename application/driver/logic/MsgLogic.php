<?php

namespace app\driver\logic;
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

    const DRIVER_NOT_EXCEED  = "司机不存在";

    const MEMBER_NOT_EXISTS  = "会员卡不存在";
    const MEMBER_IS_BUY      = "您已是该类型会员";
    const MEMBER_IS_MAX_AUTH = "您当前会员级别大于购买级别";
    const MEMBER_REPLACE_MSG = "如果更换会员，之前会员会失效";



    const COACH_USER_TYPE = 1;




}