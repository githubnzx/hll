<?php
namespace app\driver\controller;
use app\common\config\DriverConfig;
use app\driver\model\DriverModel;
use app\common\logic\MsgLogic;
use app\driver\model\MemberModel;
use app\user\logic\OrderLogic;
use app\user\logic\MsgLogic as OrderMsgLogic;
use app\user\model\OrderModel;
use app\user\model\TruckModel;
use app\user\logic\UserLogic;
use app\user\model\UsersModel;
use app\driver\logic\DriverLogic;
use app\common\push\Push;
use think\Cache;
use think\Config;
use think\cache\driver\Redis;

class Order extends Base
{
    private $week = ["周日", "周一", "周二", "周三", "周四", "周五", "周六"];
    private $robTimeLeng = 30;

    // 预约时间处理
    private function handl_order_date($order_time){
        $weeks = $this->week[date("w", $order_time)];
        $every_minute = date("H:i", $order_time);
        return $weeks . " " . $every_minute;
    }

    // 抢单列表
    public function lst(){
        $user_id = DriverLogic::getInstance()->checkToken();
        $orderIds = [];
        // 获取司机手机号
        $phone = DriverModel::getInstance()->userFind(["id"=>$user_id], "phone")["phone"] ?: 0;
        if ($phone) { // reids 判断当前司机是否是用户选的熟人订单
            $redis = new Redis(\config("cache.user"));
            $orderIds = $redis->mget($redis->keys("RobOrder:".$phone."*"));
        }
        if ($orderIds) { // 去掉选择熟人的订单
            $orderWhere["id"] = ["not in", $orderIds];
        }
        $orderWhere["driver_id"] = 0;
        $orderWhere["status"]    = 0;
        $field = "id order_id, truck_id, order_time, driver_ids, send_good_addr, collect_good_addr, is_receivables, remarks";
        $orderInfo = OrderModel::getInstance()->orderList($orderWhere, $field);
        // 判断是否是会员
        //if (!MemberModel::getInstance()->isMember(["user_id"=>$user_id, "user_type"=>MemberModel::USER_TYPE_USER])) {
        $isMember = false;
        foreach ($orderInfo as $key => &$value) {
            $order_time = $value["order_time"];
            $truckType = TruckModel::getInstance()->truckFind(["id"=>$value["truck_id"]], "type")["type"] ?: 0;
            $value["truck_name"] = DriverConfig::getInstance()->truckTypeNameId($truckType);
            $value["order_time"] = $this->handl_order_date($value["order_time"]);//$this->week[$value["type"]];
            if (!$isMember) {
                if (CURR_TIME < ($order_time + $this->robTimeLeng)) {
                    unset($orderInfo[$key]); continue;
                }
            }
        }
        return success_out($orderInfo ?: []);
    }


    // 订单详情
    public function info(){
        $user_id = DriverLogic::getInstance()->checkToken();
        $order_id= $this->request->post('order_id/d', 0);
        if(!$order_id) return error_out("", MsgLogic::PARAM_MSG);
        $field = "id, user_id, driver_id, status, total_price, send_good_lon, send_good_lat, collect_good_lon, collect_good_lat, send_good_addr, collect_good_addr, order_time";
        $orderInfo = OrderModel::getInstance()->orderFind(["id"=>$order_id], $field);
        if(!$orderInfo) return error_out("", OrderMsgLogic::ORDER_NOT_EXISTS);
        // driver_id 不存在说明是未抢订单 存在如果和当前司机不一致 说明已被其他司机预约
        if($orderInfo["driver_id"] && $orderInfo["driver_id"] !== $user_id) return error_out("", OrderMsgLogic::ORDER_BERESERVED_EXISTS);
        // 获取用户信息
        $userInfo = UsersModel::getInstance()->userFind(["id"=>$orderInfo["user_id"]], "name, phone, icon, sex");
        if(!$userInfo) return error_out("", OrderMsgLogic::ORDER_NOT_EXISTS);
        // 处理名称
        $userName = handleUserName($userInfo["name"]);
        $nameType = DriverConfig::getInstance()->userNameTypeId($userInfo["sex"]);
        $data["user_name"] = $nameType ? $userName.$nameType : $nameType;
        $data["user_phone"] = $userInfo["phone"];
        $data["user_icon"] = $userInfo["icon"];
        // 订单数据
        $data["status"] = $orderInfo["status"];
        $data["price"]  = $orderInfo["total_price"];
        $data["send_good_lon"] = $orderInfo["send_good_lon"];
        $data["send_good_lat"] = $orderInfo["send_good_lat"];
        $data["collect_good_lon"] = $orderInfo["collect_good_lon"];
        $data["collect_good_lat"] = $orderInfo["collect_good_lat"];
        $data["send_good_addr"]   = $orderInfo["send_good_addr"];
        $data["collect_good_addr"]= $orderInfo["collect_good_addr"];
        $data["order_time"] = date("H:i", $orderInfo["order_time"]);
        return success_out($data);
    }

    // 历史订单
    public function historyLst(){
        $user_id = DriverLogic::getInstance()->checkToken();
        $field = "id, truck_id, status, order_time, send_good_addr, collect_good_addr";
        $list = OrderModel::getInstance()->orderList(["driver_id"=>$user_id, "status"=>["in", [0,1,2,3]]], $field, "`status` ASC, order_time DESC");
        foreach ($list as $key => &$value) {
            $truckType = TruckModel::getInstance()->truckFind(["id"=>$value["truck_id"]], "type")["type"] ?: 0;
            $value["truck_name"] = DriverConfig::getInstance()->truckTypeNameId($truckType);
            $value["order_time"] = $this->handl_order_date($value["order_time"]);//$this->week[$value["type"]];
        }
        return success_out($list ?: []);
    }


    /*/ 订单详情
    public function infos(){
        $user_id = DriverLogic::getInstance()->checkToken();
        $order_id= $this->request->post('order_id/d', 0);
        if(!$order_id) return error_out("", MsgLogic::PARAM_MSG);
        $field = "id, truck_id, driver_id, status, price, is_evaluates";
        $orderInfo = OrderModel::getInstance()->orderFind(["id"=>$order_id], $field);
        //$truckType = TruckModel::getInstance()->truckFind(["id"=>$orderInfo["truck_id"]], "type")["type"] ?: 0;
        // 获取司机信息
        $driverInfo = DriverModel::getInstance()->userFind(["id"=>$orderInfo["driver_id"]], "id, name, phone, car_number, car_color");
        if(!$driverInfo) return error_out("", OrderMsgLogic::ORDER_NOT_EXISTS);
        // 司机车辆照片
        $truck_img = TruckModel::getInstance()->certFind(["main_id"=>$driverInfo["id"], "type"=>8], "img")["img"] ?: "";
        $evaluateContent = "";
        if($orderInfo["is_evaluates"]){
            $evaluateContent = OrderModel::getInstance()->evaluateInfo(["user_id"=>$user_id, "driver_id"=>$orderInfo["driver_id"], "order_id"=>$orderInfo["id"]], "content")["content"] ?: "";
        }
        // 货车类型
        $truckType = TruckModel::getInstance()->truckFind(["id"=>$orderInfo["truck_id"]], "type")["type"] ?: "";
        // 总评分
        $evaluateScore = OrderModel::getInstance()->evaluateColumn(["driver_id"=>$orderInfo["driver_id"]], "star_level");
        $totalNumber = count($evaluateScore);
        $totalScore = bcdiv(array_sum($evaluateScore), $totalNumber, 1);
        // 评论
        $driverInfo["truck_img"]  = $truck_img;
        $orderInfo["driver_name"] = $driverInfo["name"];
        $orderInfo["driver_phone"]= $driverInfo["phone"];
        $orderInfo["driver_truck_img"] = $truck_img;
        $orderInfo["driver_car_number"]= $driverInfo["car_number"];
        $orderInfo["total_score"] = $totalScore;
        $orderInfo["truck_type"]  = DriverConfig::getInstance()->truckTypeNameId($truckType);
        return success_out($orderInfo ?: []);
    }*/






}
