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

//    // 抢单列表
//    public function robLst(){
//        $user_id = DriverLogic::getInstance()->checkToken();
//        $orderIds = $data = [];
//        // redis
//        $redis = new Redis(\config("cache.driver"));
//        // redis中取订单数据
//        $orderList = $redis->mget($redis->keys("RobOrderData:*")) ?: [];
//        foreach ($orderList as $key => $value) {
//            $orderInfo = json_decode($value, true);
//            // 货车信息
//            $truckType = TruckModel::getInstance()->truckFind(["id"=>$orderInfo["truck_id"]], "type");//["type"] ?: 0;
//            $orderInfo["truck_name"] = DriverConfig::getInstance()->truckTypeNameId($truckType);
//            $orderInfo["order_time"] = $this->handl_order_date($orderInfo["order_time"]);//$this->week[$value["type"]];
//            // 用户信息
//            $userInfo = UsersModel::getInstance()->userFind(["id"=>$orderInfo["user_id"]], "name, phone, icon, sex");
//            if ($userInfo) { // 处理名称
//                $userName = handleUserName($userInfo["name"]);
//                $nameType = DriverConfig::getInstance()->userNameTypeId($userInfo["sex"]);
//                $order["user_name"]  = $nameType ? $userName.$nameType : $nameType;
//                $order["user_phone"] = $userInfo["phone"];
//                $order["user_icon"]  = $userInfo["icon"];
//            } else {
//                $order["user_name"]  = "未知";
//                $order["user_phone"] = "";
//                $order["user_icon"]  = "";
//            }
//            // 订单信息
//            $order["order_id"] = $orderInfo["id"];
//            $order["truck_id"] = $orderInfo["truck_id"];
//            $order["order_time"] = $orderInfo["order_time"];
//            $order["send_good_lon"] = $orderInfo["send_good_lon"];
//            $order["send_good_lat"] = $orderInfo["send_good_lat"];
//            $order["collect_good_lon"] = $orderInfo["collect_good_lon"];
//            $order["collect_good_lat"] = $orderInfo["collect_good_lat"];
//            $order["send_good_addr"] = $orderInfo["send_good_addr"];
//            $order["collect_good_addr"] = $orderInfo["collect_good_addr"];
//            $order["is_receivables"] = $orderInfo["is_receivables"];
//            $order["remarks"] = $orderInfo["remarks"];
//            $order["status"] = isset($orderInfo["status"]) ? $orderInfo["status"] : 0;
//            $order["price"] = $orderInfo["price"];
//            $order["truck_name"] = $orderInfo["truck_name"];
//            $data[] = $order;
//        }
//        return success_out($data, MsgLogic::SUCCESS);
//    }


//    // 抢单列表
//    public function robLst(){
//        $user_id = DriverLogic::getInstance()->checkToken();
//        $orderIds = $data = [];
//        $driverInfo = DriverModel::getInstance()->userFind(["id"=>$user_id], "phone, is_register, audit_status");
////        if (!$driverInfo || $driverInfo["is_register"] === 0) return error_out("", "尽快上传资料");
////        if ($driverInfo["audit_status"] !== 2) return error_out("", "资料审核中，不可抢单");
//        // redis
//        $redis = new Redis(\config("cache.driver"));
//        if ($driverInfo["phone"]) { // reids 判断当前司机是否是用户选的熟人订单
//            $orderIds = $redis->mget($redis->keys("RobOrder:".$driverInfo["phone"]."*"));
//        }
//        $order_time = 0;
//        if (!$orderIds) { // 去掉选择熟人的订单
//            // 查询是否是会员
//            $memberInfo = MemberModel::getInstance()->memberUserFind(["driver_id"=>$user_id, "end_time"=>["EGT", CURR_TIME]], "id, type, limit_second, up_limit_number");
//            if ($memberInfo) { // 有会员卡
//                $orderCount = OrderModel::getInstance()->orderCount(["driver_id"=>$user_id], "id"); // 获取当前司机当天抢单次数
//                // 会员类型
//                if ($memberInfo["type"] === 1) { // 有限制
//                    $order_time = $orderCount ? $memberInfo["limit_second"] : $order_time;
//                }
//            } else {
//                $order_time = MemberModel::MEMBER_DEFAULT_TIME;
//            }
//        }
//        //$orderWhere["driver_id"] = 0;
//        //$orderWhere["status"]    = 0;
//        //$field = "id order_id, truck_id, order_time, driver_ids, send_good_addr, collect_good_addr, is_receivables, remarks";
//        //$orderInfo = OrderModel::getInstance()->orderList($orderWhere, $field);
//        // redis中取订单数据
//        $orderList = $redis->mget($redis->keys("RobOrderData:*")) ?: [];
//        foreach ($orderList as $key => $value) {
//            $orderInfo = json_decode($value, true);
//            $orderTime = $orderInfo["order_time"];
//            $day_order_time = (int) bcadd($orderTime, $order_time);
//            if (CURR_TIME >= $day_order_time && $orderInfo["status"] == 2) {
//                // 货车信息
//                $truckType = TruckModel::getInstance()->truckFind(["id"=>$orderInfo["truck_id"]], "type");//["type"] ?: 0;
//                $orderInfo["truck_name"] = DriverConfig::getInstance()->truckTypeNameId($truckType);
//                $orderInfo["order_time"] = $this->handl_order_date($orderInfo["order_time"]);//$this->week[$value["type"]];
//                // 用户信息
//                $userInfo = UsersModel::getInstance()->userFind(["id"=>$orderInfo["user_id"]], "name, phone, icon, sex");
//                if ($userInfo) { // 处理名称
//                    $userName = handleUserName($userInfo["name"]);
//                    $nameType = DriverConfig::getInstance()->userNameTypeId($userInfo["sex"]);
//                    $order["user_name"]  = $nameType ? $userName.$nameType : $nameType;
//                    $order["user_phone"] = $userInfo["phone"];
//                    $order["user_icon"]  = $userInfo["icon"];
//                } else {
//                    $order["user_name"]  = "未知";
//                    $order["user_phone"] = "";
//                    $order["user_icon"]  = "";
//                }
//                // 订单信息
//                $order["order_id"] = $orderInfo["id"];
//                $order["truck_id"] = $orderInfo["truck_id"];
//                $order["order_time"] = $orderInfo["order_time"];
//                $order["send_good_lon"] = $orderInfo["send_good_lon"];
//                $order["send_good_lat"] = $orderInfo["send_good_lat"];
//                $order["collect_good_lon"] = $orderInfo["collect_good_lon"];
//                $order["collect_good_lat"] = $orderInfo["collect_good_lat"];
//                $order["send_good_addr"] = $orderInfo["send_good_addr"];
//                $order["collect_good_addr"] = $orderInfo["collect_good_addr"];
//                $order["is_receivables"] = $orderInfo["is_receivables"];
//                $order["remarks"] = $orderInfo["remarks"];
//                $order["status"] = isset($orderInfo["status"]) ? $orderInfo["status"] : 0;
//                $order["price"] = $orderInfo["price"];
//                $order["truck_name"] = $orderInfo["truck_name"];
//                $data[$orderTime] = $order;
//            }
//        }
//        krsort($data);
//        $dataAr = array_values($data);
//        return success_out($dataAr, MsgLogic::SUCCESS);
//    }

    // 抢单列表
    public function robLst(){
        $user_id = DriverLogic::getInstance()->checkToken();
        $orderIds = $data = $dataAr = [];
        $driverInfo = DriverModel::getInstance()->userFind(["id"=>$user_id], "phone, is_register, audit_status");
        // redis
        $redis = new Redis(\config("cache.driver"));
        // redis中取订单数据
        $orderList = $redis->mget($redis->keys("RobOrderData:*")) ?: [];
        foreach ($orderList as $key => $value) {
            $orderInfo = json_decode($value, true);
            $orderTime = $orderInfo["order_time"];
            if ($orderInfo["status"] == 2) {
                // 货车信息
                $truckType = TruckModel::getInstance()->truckFind(["id"=>$orderInfo["truck_id"]], "type");//["type"] ?: 0;
                $orderInfo["truck_name"] = DriverConfig::getInstance()->truckTypeNameId($truckType);
                $orderInfo["order_time"] = $this->handl_order_date($orderInfo["order_time"]);//$this->week[$value["type"]];
                // 用户信息
                $userInfo = UsersModel::getInstance()->userFind(["id"=>$orderInfo["user_id"]], "name, phone, icon, sex");
                if ($userInfo) { // 处理名称
                    $userName = handleUserName($userInfo["name"]);
                    $nameType = DriverConfig::getInstance()->userNameTypeId($userInfo["sex"]);
                    $order["user_name"]  = $userName ? $userName.$nameType : $userInfo["phone"];
                    $order["user_phone"] = $userInfo["phone"];
                    $order["user_icon"]  = $userInfo["icon"];
                } else {
                    $order["user_name"]  = "";
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
                $data[$orderTime] = $order;
            }
        }
        if ($data) {
            krsort($data);
            $dataAr = array_values($data);
        }
        return success_out($dataAr, MsgLogic::SUCCESS);
    }

    // 抢单
    public function robbing(){
        $user_id = DriverLogic::getInstance()->checkToken();
        $order_id= $this->request->post('order_id/d', 0);
        if(!$order_id) return error_out("", MsgLogic::PARAM_MSG);
        // 检测是否未完成订单
        $isExistsOrder = OrderModel::getInstance()->orderFind(["driver_id"=>$user_id, "status"=>2], "id")["id"] ?: 0;
        if($isExistsOrder) return error_out("", OrderMsgLogic::ORDER_NO_ROBBING);

        // 判断是否完善信息和缴纳押金
        $deposit = DriverModel::getInstance()->userFind(["id"=>$user_id], "is_register, deposit_status, deposit_number, audit_status, phone");
        if (!$deposit) return error_out("", "请重新登录");
        if ($deposit["is_register"] === 0) return error_out("", "尽快上传资料");
        if ($deposit["audit_status"] !== 2) return error_out("", "资料审核中，不可抢单");
        if ($deposit["deposit_number"] >= MemberModel::MEMBER_DEFAULT_NUMBER) return error_out("", OrderMsgLogic::DEPOSIT_STATUS_NOT);
        // 获取手机号
        //$phone = DriverModel::getInstance()->userFind(["id"=>$user_id], "phone")["phone"] ?: "";
        // redis
        $redis = new Redis(\config("cache.driver"));
        if ($deposit["phone"]) { // reids 判断当前司机是否是用户选的熟人订单
            $orderIds = $redis->mget($redis->keys("RobOrder:".$deposit["phone"]."*"));
        }
        $order_time = 0;
        $limit_second_msg = "";
        // 查询是否是会员
        $memberInfo = MemberModel::getInstance()->memberUserFind(["driver_id"=>$user_id], "id, title, type, limit_second, up_limit_number, end_time, days");
        $orderCount = OrderModel::getInstance()->orderCount(["driver_id"=>$user_id, "date"=>CURR_DATE], "id"); // 获取当前司机当天抢单次数
        if ($memberInfo && in_array($memberInfo["type"], [1,2,3])) { // 有会员卡
            if ($memberInfo["days"] === 0) { // 永久有效
                // 上限次数 0无上限
                if ($memberInfo["up_limit_number"] && $orderCount >= $memberInfo["up_limit_number"]) {
                    return error_out("", OrderMsgLogic::ORDER_UPPER_LIMIT);
                }
                // 限制秒数 0无限制
                if ($memberInfo["limit_second"] !== 0) {
                    $limit_second_msg = $memberInfo["title"] . "用户需等待". $memberInfo["limit_second"] ."秒才可抢单";
                    $order_time = $memberInfo["limit_second"];
                }
            } else {
                if ($memberInfo["end_time"] >= CURR_TIME) { // 有效期范围
                    // 上限次数 0无上限
                    if ($memberInfo["up_limit_number"] && $orderCount >= $memberInfo["up_limit_number"]) {
                        return error_out("", OrderMsgLogic::ORDER_UPPER_LIMIT);
                    }
                    // 限制秒数 0无限制
                    if ($memberInfo["limit_second"] !== 0) {
                        $limit_second_msg = $memberInfo["title"] . "用户需等待". $memberInfo["limit_second"] ."秒才可抢单";
                        $order_time = $memberInfo["limit_second"];
                    }
                } else {
                    $limit_second_msg = "非会员用户需等待". MemberModel::MEMBER_DEFAULT_TIME ."秒才可抢单";
                    if ($orderCount >= $memberInfo["up_limit_number"]) return error_out("", OrderMsgLogic::ORDER_UPPER_LIMIT);
                    $order_time = MemberModel::MEMBER_DEFAULT_TIME;
                }
            }
        } else { // 没有员卡
            $limit_second_msg = "非会员用户需等待". MemberModel::MEMBER_DEFAULT_TIME ."秒才可抢单";
            if ($orderCount >= MemberModel::NOT_MEMBER_DEFAULT_NUMBER) return error_out("", OrderMsgLogic::ORDER_UPPER_LIMIT);
            $order_time = MemberModel::MEMBER_DEFAULT_TIME;
        }
        // redis 中取订单数据
        $orderInfo = Cache::store('driver')->get("RobOrderData:" . $order_id);
        if ($orderInfo === false) return error_out("", "该订单已被预约或已失效");
        $day_order_time = (int) bcadd($orderInfo["order_time"], $order_time);
        if (CURR_TIME < $day_order_time) {
            return error_out("", $limit_second_msg);
        }
        // 查询订单是否真实存在
        $orderDataId = OrderModel::getInstance()->orderFind(["id"=>$orderInfo["id"]], "id, driver_id, status");
        if (!$orderDataId) return error_out("", OrderMsgLogic::ORDER_BERESERVED_EXISTS);
        if($orderDataId["driver_id"] !== 0 && $orderDataId["driver_id"] !== $user_id) return error_out("", OrderMsgLogic::ORDER_BERESERVED_EXISTS);
        if($orderDataId["status"] == 3) return error_out("", "订单已取消");
        // 修改订单
        $result = OrderModel::getInstance()->robbing(["id"=>$orderDataId["id"]], ["driver_id"=>$user_id]);
        if ($result === false) return error_out("", MsgLogic::SERVER_EXCEPTION);
        return success_out("", MsgLogic::SUCCESS);
    }

//    // 抢单
//    public function robbing(){
//        $user_id = DriverLogic::getInstance()->checkToken();
//        $order_id= $this->request->post('order_id/d', 0);
//        if(!$order_id) return error_out("", MsgLogic::PARAM_MSG);
//        // 判断是否完善信息和缴纳押金
//        $deposit = DriverModel::getInstance()->userFind(["id"=>$user_id], "is_register, deposit_status, deposit_number, audit_status");
//        if (!$deposit) return error_out("", "抢单失败");
//        if ($deposit["is_register"] === 0) return error_out("", "尽快上传资料");
//        if ($deposit["audit_status"] !== 2) return error_out("", "资料审核中，不可抢单");
//        if ($deposit["deposit_number"] >= MemberModel::MEMBER_DEFAULT_NUMBER) return error_out("", OrderMsgLogic::DEPOSIT_STATUS_NOT);
//        // 查询是否是会员
//        $memberInfo = MemberModel::getInstance()->memberUserFind(["driver_id"=>$user_id, "end_time"=>["EGT", CURR_TIME]], "id, type, limit_second, up_limit_number");
//        $orderCount = OrderModel::getInstance()->orderCount(["driver_id"=>$user_id, "date"=>CURR_DATE], "id"); // 获取当前司机当天抢单次数
//        if ($memberInfo) { // 有会员卡
//            if(in_array($memberInfo["type"], [1,2])) { // 会员权限
//                if ($orderCount >= $memberInfo["up_limit_number"]) return error_out("", OrderMsgLogic::ORDER_UPPER_LIMIT);
//            }
//        } else {
//            if ($orderCount >= MemberModel::NOT_MEMBER_DEFAULT_NUMBER) return error_out("", OrderMsgLogic::ORDER_UPPER_LIMIT);
//        }
//        // redis 中取订单数据
//        $orderInfo = Cache::store('driver')->get("RobOrderData:" . $order_id);
//        if ($orderInfo === false) return error_out("", OrderMsgLogic::ORDER_BERESERVED_EXISTS);
//        // 删除redis订单数据
//        //Cache::store('driver')->rm("RobOrderData:" . $order_id);
//        // 查询订单是否真实存在
//        $orderDataId = OrderModel::getInstance()->orderFind(["id"=>$orderInfo["id"], "driver_id"=>0, "status"=>2], "id")["id"] ?: 0;
//        if (!$orderDataId) return error_out("", OrderMsgLogic::ORDER_BERESERVED_EXISTS);
//        // 修改订单
//        $result = OrderModel::getInstance()->robbing(["id"=>$orderDataId], ["driver_id"=>$user_id]);
//        if ($result === false) return error_out("", MsgLogic::SERVER_EXCEPTION);
//        return success_out("", MsgLogic::SUCCESS);
//    }


    // 订单详情
    public function info(){
        $user_id = DriverLogic::getInstance()->checkToken();
        $order_id= $this->request->post('order_id/d', 0);
        if(!$order_id) return error_out("", MsgLogic::PARAM_MSG);
        $field = "id order_id, user_id, driver_id, truck_id, status, total_price price, send_good_lon, send_good_lat, collect_good_lon, collect_good_lat, send_good_addr, collect_good_addr, order_time, is_receivables, remarks";
        $orderInfo = OrderModel::getInstance()->orderFind(["id"=>$order_id], $field);
        // driver_id 不存在说明是未抢订单 存在如果和当前司机不一致 说明已被其他司机预约
        if ($orderInfo) {
            if ($orderInfo["driver_id"]) {
                if ($orderInfo["driver_id"] !== $user_id) {
                    return error_out("", OrderMsgLogic::ORDER_BERESERVED_EXISTS);
                }
            } else {
                if ($orderInfo["status"] == 3){
                    return error_out("", "订单已取消");
                }
            }
        } else {
            return error_out("", OrderMsgLogic::ORDER_NOT_EXISTS);
        }
        if($orderInfo["driver_id"] && $orderInfo["driver_id"] !== $user_id) return error_out("", OrderMsgLogic::ORDER_BERESERVED_EXISTS);
        //$orderInfo["current_order_status"] = in_array($orderInfo["status"], [0, 1]) ? 1 : 0;
        $orderInfo["current_order_status"] = $orderInfo["status"] == 2 ? 1 : 0;
        // 获取用户信息
        $userInfo = UsersModel::getInstance()->userFind(["id"=>$orderInfo["user_id"]], "name, phone, icon, sex");
        if(!$userInfo) return error_out("", OrderMsgLogic::ORDER_NOT_EXISTS);
        // 货车信息
        $truckType = TruckModel::getInstance()->truckFind(["id"=>$orderInfo["truck_id"]], "type")["type"] ?: 0;
        $orderInfo["truck_name"] = DriverConfig::getInstance()->truckTypeNameId($truckType);
        // 处理名称
        $userName = handleUserName($userInfo["name"]);
        $nameType = DriverConfig::getInstance()->userNameTypeId($userInfo["sex"]);
        $orderInfo["user_name"] = $userName ? $userName.$nameType : $userInfo["phone"];
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
        $orderInfo = OrderModel::getInstance()->orderFind(["driver_id"=>$user_id,"status"=>2], $field) ?: [];
        if ($orderInfo) { // 当前订单
            $truckType = TruckModel::getInstance()->truckFind(["id"=>$orderInfo["truck_id"]], "type")["type"] ?: 0;
            $orderInfo["truck_name"] = DriverConfig::getInstance()->truckTypeNameId($truckType);
            $orderInfo["order_time"] = $this->handl_order_date($orderInfo["order_time"]);//$this->week[$value["type"]];
            $whereAll["id"] = ["<>", $orderInfo["id"]];
            $orderInfo["current_order_status"] = 1;
            array_push($list, $orderInfo);
        }
        $whereAll["driver_id"] = $user_id;
        $orderList = OrderModel::getInstance()->orderList($whereAll, $field, "order_time DESC"); //`status` ASC, 
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
        $isExistsOrder = OrderModel::getInstance()->orderFind(["driver_id"=>$user_id, "status"=> 2], "id")["id"] ?: 0;
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

    // 到达目的地
    public function arrive(){
        $user_id = DriverLogic::getInstance()->checkToken();
        $order_id= $this->request->post('order_id/d', 0);
        $arrive_lon = $this->request->post('arrive_lon/s', "");
        $arrive_lat = $this->request->post('arrive_lat/s', "");
        if(!$order_id || !$arrive_lon || !$arrive_lat) return error_out("", MsgLogic::PARAM_MSG);
        // 删除实时经纬度
        if (getCache()->has('realtime_lon_lat:' . $user_id . "-" . $order_id)) {
            getCache()->rm('realtime_lon_lat:' . $user_id . "-" . $order_id);
        }
        // 修改订单 状态
        $result = OrderModel::getInstance()->orderEdit(["id"=>$order_id], ["status"=>4, "arrive_lon"=>$arrive_lon, "arrive_lat"=>$arrive_lat]);
        if ($result === false) return error_out("", MsgLogic::SERVER_EXCEPTION);
        return success_out("", MsgLogic::SUCCESS);
    }

    // 取消订单
    public function cancel(){
        $user_id = DriverLogic::getInstance()->checkToken();
        $order_id= $this->request->post('order_id/d', 0);
        if (!$order_id) return error_out("", MsgLogic::PARAM_MSG);
        $order_Info = OrderModel::getInstance()->orderFind(["id"=>$order_id], "code, user_id, truck_id, driver_id, is_confirm_cancel, price, fee, total_price, status, pay_type, order_time");
        if (!$order_Info) return error_out("", OrderMsgLogic::ORDER_NOT_EXISTS);
        // 判断取消订单是否已支付
        if ($order_Info["status"] === 2) {
            // 过支付时间3分钟
            if ($order_Info["order_time"] && (bcsub(CURR_TIME, $order_Info["order_time"]) > 180)) {
                if ($order_Info["total_price"] && bccomp($order_Info["total_price"], 5) === 1) {
                   // $totalPrice = bcsub($order_Info["total_price"], 5);
                } else {
                    // 金额不够5元
                    return error_out("", "订单不可取消");
                }
            } else {
                //$totalPrice = $order_Info["total_price"];
            }
            // 退款操作
            if ($order_Info["pay_type"] === 1) {  // 微信
                $order = OrderLogic::getInstance()->refundWx($order_Info['code'], $order_Info["total_price"]);
                if($order['return_code'] != 'SUCCESS' || $order['result_code'] != 'SUCCESS'){
                    Log::error('微信提现失败:' . $order_Info['code'] . '=>' . $order['err_code_des']);
                    return error_out('', $order['err_code_des']);
                }
            } else {  // 支付宝
                $order = TransferLogic::getInstance()->refundZfb($order_Info['code'], $order_Info["total_price"], "支付宝提现支付");
                if($order === false) {
                    return error_out('', "支付宝提现支付失败");
                }
            }
        }
        $result = OrderModel::getInstance()->orderCancel(["id"=>$order_id], ["status"=>3]);
        if($result === false) return error_out("", MsgLogic::PARAM_MSG);
        return success_out("", MsgLogic::SUCCESS);
    }

    // 上传司机实时经纬度
    public function RealTimeLonAndLat(){
        $user_id  = DriverLogic::getInstance()->checkToken();
        $order_id = $this->request->post('order_id/d', 0);
        $real_lon = $this->request->post('real_lon/s', "");
        $real_lat = $this->request->post('real_lat/s', "");
        if(!$order_id || !$real_lon || !$real_lat) return error_out("", MsgLogic::PARAM_MSG);
        // 存储实时司机经纬度
        getCache()->set('realtime_lon_lat:' . $user_id . "-" . $order_id, $real_lon . "," . $real_lat);
        return success_out("", MsgLogic::SUCCESS);
    }





}
