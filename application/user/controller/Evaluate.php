<?php
/**
 * Created by PhpStorm.
 * User: nzx
 * Date: 2018/02/07
 * Time: 15:00
 */

namespace app\user\controller;


use app\user\logic\UserLogic;
use app\common\logic\MsgLogic;
use app\user\logic\MsgLogic as EvaluateMsgLogic;
use app\user\model\OrderModel;
use app\user\model\EvaluateModel;
use think\Session;
use think\Cache;
use think\config;
use think\log;

class Evaluate extends Base
{
    // 添加评价
    public function add(){
        $user_id = UserLogic::getInstance()->checkToken();
        $order_id   = request()->post('order_id/d' , 0);
        $star_level = request()->post('star_level/d' , 0);  // 星级
        $content    = request()->post('content/s' , "");
        if(!$order_id || !$star_level || !$content) return error_out("", MsgLogic::PARAM_MSG);
        $order = OrderModel::getInstance()->orderFind(["id"=>$order_id], "user_id, truck_id, driver_id, is_evaluates") ?: [];
        if (!$order) return error_out("", EvaluateMsgLogic::ORDER_NOT_EXISTS);
        if ($content) {
            if(!statisticsContentRangeValid($content, 0, 300)) return error_out("", EvaluateMsgLogic::EVALUATE_CONTENT);
        }
        $data["user_id"]  = $order["user_id"];
        $data["truck_id"] = $order["truck_id"];
        $data["driver_id"]= $order["driver_id"];
        $data["order_id"] = $order_id;
        $data["star_level"]= $star_level;
        $data["content"]   = $content;
        $result = EvaluateModel::getInstance()->evaluateAdd($data);
        if($result === false) return error_out("", MsgLogic::SERVER_EXCEPTION);
        return success_out("", MsgLogic::SUCCESS);
    }

}