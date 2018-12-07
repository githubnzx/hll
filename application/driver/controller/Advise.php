<?php
/**
 * Created by PhpStorm.
 * User: php_s
 * Date: 2018/3/13
 * Time: 10:20
 */

namespace app\driver\controller;
use app\driver\model\AdviseModel;
use app\driver\model\DriverModel;
use app\driver\logic\MsgLogic as UserMsgLogic;
use app\driver\logic\DriverLogic;
use app\common\logic\MsgLogic;
use think\Cache;
use think\config;

class Advise extends Base
{

    public function add()
    {
        $user_id = DriverLogic::getInstance()->checkToken();
        $title   = $this->request->param('title/s', "");
        $content = $this->request->param('content/s', "");
        if(!$title)   return error_out('', UserMsgLogic::ADVISE_MSG_TITLE);
        if(!$content) return error_out('', UserMsgLogic::ADVISE_MSG_CONTENT);
        if(!statisticsContentRangeValid($content, 10, 200)){
            return error_out('', UserMsgLogic::ADVISE_MSG_CONTENT_RANGE);
        }
        $advise["user_id"] = $user_id;
        $advise["date"] = currZeroDateToTime();
        $advise["user_type"] = AdviseModel::USER_TYPE;
        $advise_number = AdviseModel::getInstance()->adviseFind(["user_id"=>$user_id, "user_type"=>AdviseModel::USER_TYPE], "count(id) id")["id"] ?: 0;
        if($advise_number >= 5) return error_out("", UserMsgLogic::ADVISE_MSG_EXCEED);
        $data["user_id"] = $user_id;
        $data["title"]   = $title;
        $data["content"] = $content;
        $data["date"]    = currZeroDateToTime();
        $data["user_type"] = AdviseModel::USER_TYPE;
        $result = AdviseModel::getInstance()->adviseAdd($data);
        if ($result === false) return error_out("", MsgLogic::SERVER_EXCEPTION);
        return success_out("", UserMsgLogic::ADVISE_MSG_SUCCESS);
    }
}