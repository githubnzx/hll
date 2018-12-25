<?php
namespace app\driver\controller;
use app\common\config\DriverConfig;
use app\driver\model\DriverModel;
use app\common\logic\MsgLogic;
use app\driver\model\MemberModel;
use app\user\logic\OrderLogic;
use app\user\logic\MsgLogic as OrderMsgLogic;
use app\driver\model\OrderModel;
use app\user\model\TruckModel;
use app\user\logic\UserLogic;
use app\user\model\UsersModel;
use app\driver\logic\DriverLogic;
use app\common\push\Push;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
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
    public function robLst(){
        $user_id = DriverLogic::getInstance()->checkToken();
        $orderIds = $data = [];
        $driverInfo = DriverModel::getInstance()->userFind(["id"=>$user_id], "phone, is_register");
        //if (!$driverInfo || $driverInfo["is_register"] === 0) return error_out("", "尽快上传资料");
        // redis
        $redis = new Redis(\config("cache.driver"));
        if ($driverInfo["phone"]) { // reids 判断当前司机是否是用户选的熟人订单
            $orderIds = $redis->mget($redis->keys("RobOrder:".$driverInfo["phone"]."*"));
        }
        $order_time = 0;
        if (!$orderIds) { // 去掉选择熟人的订单
            // 查询是否是会员
            $memberInfo = MemberModel::getInstance()->memberUserFind(["driver_id"=>$user_id, "end_time"=>["EGT", CURR_TIME]], "id, type, limit_second, up_limit_number");
            if ($memberInfo) { // 有会员卡
                $orderCount = OrderModel::getInstance()->orderCount(["driver_id"=>$user_id], "id"); // 获取当前司机当天抢单次数
                // 会员类型
                if ($memberInfo["type"] === 1) { // 有限制
                    $order_time = $orderCount ? $memberInfo["limit_second"] : $order_time;
                }
            } else {
                $order_time = MemberModel::MEMBER_DEFAULT_TIME;
            }
        }
        //$orderWhere["driver_id"] = 0;
        //$orderWhere["status"]    = 0;
        //$field = "id order_id, truck_id, order_time, driver_ids, send_good_addr, collect_good_addr, is_receivables, remarks";
        //$orderInfo = OrderModel::getInstance()->orderList($orderWhere, $field);
        // redis中取订单数据
        $orderList = $redis->mget($redis->keys("RobOrderData:*")) ?: [];
        foreach ($orderList as $key => $value) {
            $orderInfo = json_decode($value, true);
            $orderTime = $orderInfo["order_time"];
            $day_order_time = (int) bcadd($orderTime, $order_time);
            if (CURR_TIME >= $day_order_time) {
                // 货车信息
                $truckType = TruckModel::getInstance()->truckFind(["id"=>$orderInfo["truck_id"]], "type");//["type"] ?: 0;
                $orderInfo["truck_name"] = DriverConfig::getInstance()->truckTypeNameId($truckType);
                $orderInfo["order_time"] = $this->handl_order_date($orderInfo["order_time"]);//$this->week[$value["type"]];
                // 用户信息
                $userInfo = UsersModel::getInstance()->userFind(["id"=>$orderInfo["user_id"]], "name, phone, icon, sex");
                if ($userInfo) { // 处理名称
                    $userName = handleUserName($userInfo["name"]);
                    $nameType = DriverConfig::getInstance()->userNameTypeId($userInfo["sex"]);
                    $order["user_name"]  = $nameType ? $userName.$nameType : $nameType;
                    $order["user_phone"] = $userInfo["phone"];
                    $order["user_icon"]  = $userInfo["icon"];
                } else {
                    $order["user_name"]  = "未知";
                    $order["user_phone"] = "";
                    $order["user_icon"]  = "";
                }
                // 订单信息
                $order["order_id"] = $orderInfo["id"];
                $order["truck_id"] = $orderInfo["truck_id"];
                $order["order_time"] = $orderInfo["order_time"];
                $order["send_good_lon"] = $orderInfo["send_good_lon"];
                $order["send_good_lat"] = $orderInfo["send_good_lat"];
                $order["collect_good_lon"] = $orderInfo["collect_good_lon"];
                $order["collect_good_lat"] = $orderInfo["collect_good_lat"];
                $order["send_good_addr"] = $orderInfo["send_good_addr"];
                $order["collect_good_addr"] = $orderInfo["collect_good_addr"];
                $order["is_receivables"] = $orderInfo["is_receivables"];
                $order["remarks"] = $orderInfo["remarks"];
                $order["status"] = isset($orderInfo["status"]) ? $orderInfo["status"] : 0;
                $order["price"] = $orderInfo["price"];
                $order["truck_name"] = $orderInfo["truck_name"];
                $data[] = $order;
            }
        }
        return success_out($data, MsgLogic::SUCCESS);
    }

    // 抢单
    public function robbing(){
        $user_id = DriverLogic::getInstance()->checkToken();
        $order_id= $this->request->post('order_id/d', 0);
        if(!$order_id) return error_out("", MsgLogic::PARAM_MSG);
        // 判断是否完善信息和缴纳押金
        $deposit = DriverModel::getInstance()->userFind(["id"=>$user_id], "is_register, deposit_status, deposit_number");
        if (!$deposit) return error_out("", "失败");
        if ($deposit["is_register"] === 0) return error_out("", "尽快上传资料");
        if ($deposit["deposit_number"] >= 3) return error_out("", OrderMsgLogic::DEPOSIT_STATUS_NOT);
        // 查询是否是会员
        $memberInfo = MemberModel::getInstance()->memberUserFind(["driver_id"=>$user_id, "end_time"=>["EGT", CURR_TIME]], "id, type, limit_second, up_limit_number");
        $orderCount = OrderModel::getInstance()->orderCount(["driver_id"=>$user_id], "id"); // 获取当前司机当天抢单次数
        if ($memberInfo) { // 有会员卡
            if(in_array($memberInfo["type"], [1,2])) { // 会员权限
                if ($orderCount >= $memberInfo["up_limit_number"]) return error_out("", OrderMsgLogic::ORDER_UPPER_LIMIT);
            }
        } else {
            if ($orderCount >= MemberModel::MEMBER_DEFAULT_NUMBER) return error_out("", OrderMsgLogic::ORDER_UPPER_LIMIT);
        }
        // redis 中取订单数据
        $orderInfo = Cache::store('driver')->get("RobOrderData:" . $order_id);
        if ($orderInfo === false) return error_out("", OrderMsgLogic::ORDER_BERESERVED_EXISTS);
        // 删除redis订单数据
        //Cache::store('driver')->rm("RobOrderData:" . $order_id);
        // 查询订单是否真实存在
        $orderDataId = OrderModel::getInstance()->orderFind(["id"=>$orderInfo["id"], "driver_id"=>0, "status"=>0], "id")["id"] ?: 0;
        if (!$orderDataId) return error_out("", OrderMsgLogic::ORDER_BERESERVED_EXISTS);
        // 修改订单
        $result = OrderModel::getInstance()->robbing(["id"=>$orderDataId], ["driver_id"=>$user_id]);
        if ($result === false) return error_out("", MsgLogic::SERVER_EXCEPTION);
        return success_out("", MsgLogic::SUCCESS);
    }


    // 订单详情
    public function info(){
        $user_id = DriverLogic::getInstance()->checkToken();
        $order_id= $this->request->post('order_id/d', 0);
        if(!$order_id) return error_out("", MsgLogic::PARAM_MSG);
        $field = "id order_id, user_id, driver_id, truck_id, status, total_price price, send_good_lon, send_good_lat, collect_good_lon, collect_good_lat, send_good_addr, collect_good_addr, order_time, is_receivables, remarks";
        $orderInfo = OrderModel::getInstance()->orderFind(["id"=>$order_id], $field);
        if(!$orderInfo) return error_out("", OrderMsgLogic::ORDER_NOT_EXISTS);
        // driver_id 不存在说明是未抢订单 存在如果和当前司机不一致 说明已被其他司机预约
        if($orderInfo["driver_id"] && $orderInfo["driver_id"] !== $user_id) return error_out("", OrderMsgLogic::ORDER_BERESERVED_EXISTS);
        // 获取用户信息
        $userInfo = UsersModel::getInstance()->userFind(["id"=>$orderInfo["user_id"]], "name, phone, icon, sex");
        if(!$userInfo) return error_out("", OrderMsgLogic::ORDER_NOT_EXISTS);
        // 货车信息
        $truckType = TruckModel::getInstance()->truckFind(["id"=>$orderInfo["truck_id"]], "type")["type"] ?: 0;
        $orderInfo["truck_name"] = DriverConfig::getInstance()->truckTypeNameId($truckType);
        // 处理名称
        $userName = handleUserName($userInfo["name"]);
        $nameType = DriverConfig::getInstance()->userNameTypeId($userInfo["sex"]);
        $orderInfo["user_name"] = $nameType ? $userName.$nameType : $nameType;
        $orderInfo["user_phone"] = $userInfo["phone"];
        $orderInfo["user_icon"] = $userInfo["icon"];
        $orderInfo["order_time"] = $this->handl_order_date($orderInfo["order_time"]);
        unset($orderInfo["driver_id"], $orderInfo["user_id"]);
        return success_out($orderInfo);
    }

    // 历史订单
    public function lst(){
        $user_id = DriverLogic::getInstance()->checkToken();
        $orderInfo = $orderList = $list = [];
        $field = "id, truck_id, status, order_time, send_good_addr, collect_good_addr";
        $orderInfo = OrderModel::getInstance()->orderFind(["driver_id"=>$user_id,"status"=>["in", [0,1]]], $field) ?: [];
        if ($orderInfo) { // 当前订单
            $truckType = TruckModel::getInstance()->truckFind(["id"=>$orderInfo["truck_id"]], "type")["type"] ?: 0;
            $orderInfo["truck_name"] = DriverConfig::getInstance()->truckTypeNameId($truckType);
            $orderInfo["order_time"] = $this->handl_order_date($orderInfo["order_time"]);//$this->week[$value["type"]];
            $whereAll["id"] = ["<>", $orderInfo["id"]];
            $orderInfo["current_order_status"] = 1;
            array_push($list, $orderInfo);
        }
        $whereAll["driver_id"] = $user_id;
        $orderList = OrderModel::getInstance()->orderList($whereAll, $field, "`status` ASC, order_time DESC");
        foreach ($orderList as $key => &$value) {
            $truckType = TruckModel::getInstance()->truckFind(["id"=>$value["truck_id"]], "type")["type"] ?: 0;
            $value["truck_name"] = DriverConfig::getInstance()->truckTypeNameId($truckType);
            $value["order_time"] = $this->handl_order_date($value["order_time"]);//$this->week[$value["type"]];
            $value["current_order_status"] = 0;
            array_push($list, $value);
        }
        return success_out($list ?: []);
    }
    // 检测订单
    public function isExistOrder(){
        $user_id = DriverLogic::getInstance()->checkToken();
        $isExistsOrder = OrderModel::getInstance()->orderFind(["driver_id"=>$user_id, "status"=>["in", [0,1]]], "id")["id"] ?: 0;
        $status = $isExistsOrder ? 1 : 0;
        $data["order_id"] = $isExistsOrder;
        $data["status"]   = $status;
        return success_out($data);
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
