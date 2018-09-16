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



    const COACH_USER_TYPE = 1;




}