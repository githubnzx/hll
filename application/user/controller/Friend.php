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
use app\user\logic\MsgLogic as FriendMsgLogic;
use app\user\model\FriendModel;
use app\user\model\OrderModel;
use app\driver\model\DriverModel;
use app\user\model\EvaluateModel;
use think\Session;
use think\Cache;
use think\config;
use think\log;

class Friend extends Base
{
    // 收藏熟人
    public function collect(){
        $user_id = UserLogic::getInstance()->checkToken();
        $order_id= $this->request->post('order_id/d', 0);
        if(!$order_id) return error_out("", MsgLogic::PARAM_MSG);
        $field = "id, truck_id, driver_id, status, price, is_evaluates";
        $orderInfo = OrderModel::getInstance()->orderFind(["id"=>$order_id], $field);
        // 获取司机信息
        $driverInfo = DriverModel::getInstance()->userFind(["id"=>$orderInfo["driver_id"]], "name, phone");
        if(!$driverInfo) return error_out("", FriendMsgLogic::ORDER_NOT_EXISTS);
        $data["user_id"] = $user_id;
        $data["driver_name"] = $driverInfo["name"];
        $data["driver_phone"]= $driverInfo["phone"];
        $result = FriendModel::getInstance()->friendAdd($data);
        if($result === false) return error_out("", MsgLogic::SERVER_EXCEPTION);
        return success_out("", MsgLogic::SUCCESS);
    }
    // 列表
    public function lst(){
        $user_id = UserLogic::getInstance()->checkToken();
        $content = $this->request->post('content/s', "");
        //if ($name)  if (!UserLogic::getInstance()->check_name($name)) return error_out("", FriendMsgLogic::ORDER_USER_NAME);
        //if ($phone) if (!UserLogic::getInstance()->check_mobile($phone)) return error_out("", UserLogic::USER_PHONE_MSG);
        //if ($name) $where["driver_name"] = ["LIKE", "%".$name."%"];
        //if ($phone) $where["driver_phone"] = ["LIKE", "%".$phone."%"];
        $whereOr["driver_name"] = ["LIKE", "%".$content."%"];
        $whereOr["driver_phone"] = ["LIKE", "%".$content."%"];
        $where["user_id"] = $user_id;
        $list = FriendModel::getInstance()->friendList($where, $whereOr, "id, user_id, driver_name, driver_phone");
        if($list === false) return error_out("", MsgLogic::SERVER_EXCEPTION);
        return success_out($list);
    }
    // 熟人编辑
    public function edit(){
        $user_id = UserLogic::getInstance()->checkToken();
        $id   = $this->request->post('id/s', "");
        $name = $this->request->post('name/s', "");
        $phone= $this->request->post('phone/s', "");
        if (!$id) return error_out("", MsgLogic::PARAM_MSG);
        if ($name)  if(!UserLogic::getInstance()->check_name($name)) return error_out("", FriendMsgLogic::ORDER_USER_NAME);
        if ($phone) {
            if(!UserLogic::getInstance()->check_mobile($phone)) return error_out("", UserLogic::USER_PHONE_MSG);
            $friend_id = FriendModel::getInstance()->friendFind(["driver_phone"=>$phone], "id")["id"] ?: 0;
            if (!$friend_id) return error_out("", FriendMsgLogic::FRIEND_PHONE_EXISTS);
        }
        $result = FriendModel::getInstance()->friendEdit(["id"=>$id, "user_id"=>$user_id], ["driver_name"=>$name, "driver_phone"=>$phone]);
        if($result === false) return error_out("", MsgLogic::SERVER_EXCEPTION);
        return success_out("", MsgLogic::SUCCESS);
    }
    // 熟人删除
    public function del(){
        $user_id = UserLogic::getInstance()->checkToken();
        $id   = $this->request->post('id/s', "");
        if (!$id) return error_out("", MsgLogic::PARAM_MSG);
        $result = FriendModel::getInstance()->friendEdit(["id"=>$id, "user_id"=>$user_id], ["is_del"=>1]);
        if ($result === false) return error_out("", MsgLogic::SERVER_EXCEPTION);
        return success_out("", MsgLogic::SUCCESS);
    }


    public function add(){
        $user_id = UserLogic::getInstance()->checkToken();
        $name  = $this->request->post('name/s', "");
        $phone = $this->request->post('phone/s', "");
        if(!$name || !$phone) return error_out("", MsgLogic::PARAM_MSG);
        $id = FriendModel::getInstance()->friendFind(["driver_phone"=>$phone], "id")["id"] ?: 0;
        if ($id) return error_out("", FriendMsgLogic::FRIEND_PHONE_EXISTS);
        $data["user_id"] = $user_id;
        $data["driver_name"] = $name;
        $data["driver_phone"]= $phone;
        $result = FriendModel::getInstance()->friendAdd($data);
        if($result === false) return error_out("", MsgLogic::SERVER_EXCEPTION);
        return success_out("", MsgLogic::SUCCESS);
    }


}