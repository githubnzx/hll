<?php
namespace app\driver\controller;
use app\common\config\DriverConfig;
use app\driver\model\DriverModel;
use app\common\logic\MsgLogic;
use app\user\logic\OrderLogic;
use app\user\logic\MsgLogic as OrderMsgLogic;
use app\user\model\OrderModel;
use app\user\model\TruckModel;
use app\user\logic\UserLogic;
use app\driver\logic\DriverLogic;
use app\common\push\Push;
use think\Cache;
use think\Config;
use think\cache\driver\Redis;

class Order extends Base
{
    private $week = ["周日", "周一", "周二", "周三", "周四", "周五", "周六"];

    // 预约时间处理
    private function handl_order_date($order_time){
        $weeks = $this->week[date("w", $order_time)];
        $every_minute = date("H:i", $order_time);
        return $weeks . " " . $every_minute;
    }

    // 抢单列表
    public function lst(){
        $user_id = DriverLogic::getInstance()->checkToken();
        //$redis = new Redis(\config("cache.user"));
        //$redis->keys("RobOrder".$phone."*");
        // 获取司机手机号
        $phone = DriverModel::getInstance()->userFind(["id"=>$user_id], "phone")["phone"] ?: 0;
        if ($phone) {
            $redis = new Redis(\config("cache.user"));
            $orderIds = $redis->mget($redis->keys("RobOrder".$phone."*"));
        }
        $field = "id order_id, truck_id, order_time, send_good_addr, collect_good_addr, is_receivables, remarks";
        $orderInfo = OrderModel::getInstance()->orderList(["driver_id"=>0, "status"=>0], $field);
        foreach ($orderInfo as $key => &$value) {
            $truckType = TruckModel::getInstance()->truckFind(["id"=>$value["truck_id"]], "type")["type"] ?: 0;
            $value["truck_name"] = DriverConfig::getInstance()->truckTypeNameId($truckType);
            $value["order_time"] = $this->handl_order_date($value["order_time"]);//$this->week[$value["type"]];
        }
        return success_out($orderInfo ?: []);
    }


    // 订单详情
    public function info(){
        $user_id = UserLogic::getInstance()->checkToken();
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
    }






}
