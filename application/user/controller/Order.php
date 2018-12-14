<?php
namespace app\user\controller;
use app\common\config\DriverConfig;
use app\driver\model\DriverModel;
use app\common\logic\MsgLogic;
use app\user\logic\OrderLogic;
use app\user\logic\MsgLogic as OrderMsgLogic;
use app\user\model\OrderModel;
use app\user\model\TruckModel;
use app\user\model\UsersModel;
use app\user\logic\UserLogic;
use app\common\push\Push;
use think\Cache;
use think\Config;


class Order extends Base
{
    private $week = ["周日", "周一", "周二", "周三", "周四", "周五", "周六"];

    // 下单
    public function index()
    {
        $user_id = UserLogic::getInstance()->checkToken();
        $truck_id = $this->request->post('truck_id/d', 0);
        $send_lon = $this->request->post('send_lon/s', "");
        $send_lat = $this->request->post('send_lat/s', "");
        $collect_lon = $this->request->post('collect_lon/s', "");
        $collect_lat = $this->request->post('collect_lat/s', "");
        $send_addr   = $this->request->post('send_addr/s', "");
        $collect_addr= $this->request->post('collect_addr/s', "");
        $driver_ids  = $this->request->post('driver_ids/a', []);  // 熟人司机
        $remarks     = $this->request->post('remarks/s', "");     // 备注
        $contacts    = $this->request->post('contacts/s', "");    // 联系人
        $phone       = $this->request->post('phone/s', "");       // 联系人电话
        $isPlaceOrder= $this->request->post('is_place_order/d', 0); // 是否代收款
        $isReceivables= $this->request->post('is_receivables/d', 0); // 是否预约
        $order_time  = strtotime($this->request->post('order_time/s', ""));  // 预约时间
        $fee_price   = strtotime($this->request->post('fee_price/s', ""));   // 小费
        $consignee_phone = $this->request->post('consignee_phone/s', "");
        $consignee_name  = $this->request->post('consignee_name/s', "");
        //$kilometers  = $this->request->post('kilometers/d', 0);    // 公里数
        if (!$truck_id || !$send_lon || !$send_lat || !$collect_lon || !$collect_lat || !$send_addr || !$collect_addr || !$consignee_phone || !$consignee_name) {
            return error_out("", MsgLogic::PARAM_MSG);
        }
        // 查询货车是否存在
        $trucInfo = TruckModel::getInstance()->truckFind(["id"=>$truck_id], "id, type");
        if(!$trucInfo) return error_out("", OrderMsgLogic::TRUCK_IS_EXISTS);
        if($isReceivables){ // 是预约订单时间必填
            if(!$order_time) return error_out("", OrderMsgLogic::ORDER_IS_RECEIVABLE);
        }
        if(!UserLogic::getInstance()->check_name($contacts)) return error_out("", OrderMsgLogic::ORDER_USER_NAME);
        if(!UserLogic::getInstance()->check_mobile($phone)) return error_out("", UserLogic::USER_PHONE_MSG);
        // 检测是否未完成订单
        $isExistsOrder = OrderModel::getInstance()->orderFind(["user_id"=>$user_id, "status"=>["in", [0,1]]], "id")["id"] ?: 0;
        if($isExistsOrder) return error_out("", OrderMsgLogic::ORDER_IS_EXISTS);
        // 费用计算
        $result = OrderLogic::getInstance()->obtainKilometre($send_lon, $send_lat, $collect_lon, $collect_lat);
        if (!$result) return error_out("", MsgLogic::PARAM_MSG);
        $price = OrderLogic::getInstance()->imputedPrice($result["result"]["routes"][0]["distance"], $trucInfo["type"], $fee_price);
        $order = [
            "code"          => OrderLogic::getInstance()->makeCode(),
            "user_id"       => $user_id,
            "truck_id"      => $truck_id,
            "driver_ids"    => $driver_ids ? implode(",", $driver_ids) : "",
            "send_good_lon" => $send_lon,
            "send_good_lat" => $send_lat,
            "receivables"   => $isPlaceOrder,
            "is_receivables"=> $isPlaceOrder,
            "collect_good_lon" => $collect_lon,
            "collect_good_lat" => $collect_lat,
            "send_good_addr"   => $send_addr,
            "collect_good_addr"=> $collect_addr,
            "price"   => $price,
            "total_price" => bcadd($price, $fee_price),
            "fee"     => $fee_price,
            "contacts"=> $contacts,
            "phone"   => $phone,
            "consignee_phone" => $consignee_phone,
            "consignee_name"  => $consignee_name,
            "status"  => 0,
            "remarks" => $remarks,
            "date"    => strtotime(CURR_DATE),
            "is_place_order" => $order_time ? 1 : 0,
            "order_time" => $order_time ?: CURR_TIME
        ];
        // 下单
        $order_id = OrderModel::getInstance()->orderInsert($order);
        if($order_id === false) return error_out("", MsgLogic::SERVER_EXCEPTION);
        return success_out(["order_id"=>$order_id], MsgLogic::SUCCESS);
    }

    // 计算总价格
    public function price()
    {
        $user_id = UserLogic::getInstance()->checkToken();
        $truck_id = $this->request->post('truck_id/d', 0);
        $fee_price   = $this->request->post('fee_price/s', "");   // 小费
        $send_lon = $this->request->post('send_lon/s', "");
        $send_lat = $this->request->post('send_lat/s', "");
        $collect_lon = $this->request->post('collect_lon/s', "");
        $collect_lat = $this->request->post('collect_lat/s', "");
        //$kilometers  = $this->request->post('kilometers/s', 0);    // 公里数
        if (!$truck_id || !$send_lon || !$send_lat || !$collect_lon || !$collect_lat) return error_out("", MsgLogic::PARAM_MSG);
        // 查询货车是否存在
        $trucInfo = TruckModel::getInstance()->truckFind(["id"=>$truck_id], "id, type");
        if(!$trucInfo) return error_out("", OrderMsgLogic::TRUCK_IS_EXISTS);
        // 费用计算
        $result = OrderLogic::getInstance()->obtainKilometre($send_lon, $send_lat, $collect_lon, $collect_lat);
        if (!$result) return error_out("", MsgLogic::PARAM_MSG);
        // 费用计算
        $price = OrderLogic::getInstance()->imputedPrice($result["result"]["routes"][0]["distance"], $trucInfo["type"], $fee_price);
        return success_out(["total_price"=>$price], MsgLogic::SUCCESS);
    }

    // 历史订单
    public function lst(){
        $user_id = UserLogic::getInstance()->checkToken();
        $orderInfo = $orderList = $list = [];
        $field = "id, driver_id, truck_id, status, order_time, send_good_addr, collect_good_addr";
        $orderInfo = OrderModel::getInstance()->orderFind(["user_id"=>$user_id,"status"=>["in", [0,1]]], $field) ?: [];
        $is_booked = 1;
        if ($orderInfo) { // 当前订单
            if ($orderInfo["driver_id"] === 0) {
                $orderInfo["is_booked"] = 0;
            } else {
                $orderInfo["is_booked"] = $is_booked;
            }
            $truckType = TruckModel::getInstance()->truckFind(["id"=>$orderInfo["truck_id"]], "type")["type"] ?: 0;
            $orderInfo["truck_name"] = DriverConfig::getInstance()->truckTypeNameId($truckType);
            $orderInfo["order_time"] = $this->handl_order_date($orderInfo["order_time"]);//$this->week[$value["type"]];
            $whereAll["id"] = ["<>", $orderInfo["id"]];
            $orderInfo["current_order_status"] = 1;
            array_push($list, $orderInfo);
        }
        $whereAll["user_id"] = $user_id;
        $orderList = OrderModel::getInstance()->orderList($whereAll, $field, "order_time DESC") ?: [];
        foreach ($orderList as $key => &$value) {
            $value["is_booked"] = $is_booked;
            $truckType = TruckModel::getInstance()->truckFind(["id"=>$value["truck_id"]], "type")["type"] ?: 0;
            $value["truck_name"] = DriverConfig::getInstance()->truckTypeNameId($truckType);
            $value["order_time"] = $this->handl_order_date($value["order_time"]);//$this->week[$value["type"]];
            $value["current_order_status"] = 0;
            array_push($list, $value);
        }
        return success_out($list ?: []);
    }
    // 预约时间处理
    private function handl_order_date($order_time){
        $weeks = $this->week[date("w", $order_time)];
        $every_minute = date("H:i", $order_time);
        return $weeks . " " . $every_minute;
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
        if ($evaluateScore) {
            $totalNumber = count($evaluateScore);
            $totalScore = bcdiv(array_sum($evaluateScore), $totalNumber, 1);
        } else {
            $totalScore = 0;
        }
        // 评论
        $driverInfo["truck_img"]  = $truck_img;
        $orderInfo["driver_name"] = $driverInfo["name"];
        $orderInfo["driver_phone"]= $driverInfo["phone"];
        $orderInfo["driver_truck_img"] = $truck_img;
        $orderInfo["driver_car_number"]= $driverInfo["car_number"];
        $orderInfo["total_score"] = $totalScore;
        $orderInfo["truck_type"]  = DriverConfig::getInstance()->truckTypeNameId($truckType);
        $orderInfo["current_order_status"] = in_array($orderInfo["status"], [0,1]) ? 1 : 0;
        return success_out($orderInfo ?: []);
    }

    // 取消订单
    public function cancel(){
        $user_id = UserLogic::getInstance()->checkToken();
        $order_id= $this->request->post('order_id/d', 0);
        if (!$order_id) return error_out("", MsgLogic::PARAM_MSG);
        $order_Info = OrderModel::getInstance()->orderFind(["id"=>$order_id], "user_id, truck_id, driver_id, is_confirm_cancel, price, fee, total_price");
        if (!$order_Info) return error_out("", OrderMsgLogic::ORDER_NOT_EXISTS);
        $lossPrice = "0";
        if ($order_Info["is_confirm_cancel"] === 1) { // 需要支付损失费用（司机到达发货地后）
            // 计算损失费用
            $lossPrice = bcdiv($order_Info["price"], 10, 1);
            //$result = OrderModel::getInstance()->orderEdit(["id"=>$order_id], ["loss_price"=>$lossPrice]);
        } else { // 零费用取消订单
            $result = OrderModel::getInstance()->orderEdit(["id"=>$order_id], ["status"=>3]);
            if($result === false) return error_out("", MsgLogic::PARAM_MSG);
        }
        $data["status"]    = $order_Info["is_confirm_cancel"];
        $data["loss_price"]= $lossPrice;
        $data["order_id"]  = $order_id;
        return success_out($data, MsgLogic::SUCCESS);


    }
    
    // 支付
    public function pay(){
        $user_id = UserLogic::getInstance()->checkToken();
        $order_id = $this->request->post('order_id/d', 0);
        $status = $this->request->post('status/d', 0); // 是否取消支付 0否 1是
        $pay_type = $this->request->post('pay_type/d', 0); // 1 微信 2支付宝
        if (!$order_id || !$pay_type) return error_out("", MsgLogic::PARAM_MSG);
        $order = OrderModel::getInstance()->orderFind(["id"=>$order_id], "user_id, code, truck_id, driver_id, is_confirm_cancel, price, fee, total_price, status");
        if (!$order) return error_out("", OrderMsgLogic::ORDER_NOT_EXISTS);
        if ($order["status"] != 1) return error_out("", OrderMsgLogic::ORDER_NOT_PAY);
        if ($status === 1) {
            $actualPrice = bcdiv($order["price"], 10, 1);
        } else {
            $actualPrice = $order["total_price"];
        }
        if ($pay_type == 1) { //微信
            $data['wxData'] = OrderLogic::getInstance()->payWx($order['code'], $actualPrice, url('user/pay/notifyWx', '', true, true));
        } else { // 支付宝
            $data['zfbData'] = OrderLogic::getInstance()->payZfb($order['code'], $actualPrice, url('user/pay/notifyZfb', '', true, true));
        }
        return success_out($data);
    }

    // 检测订单
    public function isExistOrder(){
        $user_id = UserLogic::getInstance()->checkToken();
        $order = OrderModel::getInstance()->orderFind(["user_id"=>$user_id, "status"=>["in", [0,1]]], "id, driver_id");
        $order_id = $status = 0;
        if ($order) {
            $order_id = $order["id"];
            $status = $order["driver_id"] ? 2 : 1;
        }
        $data["order_id"] = $order_id;
        $data["status"]   = $status;
        return success_out($data);
    }

    // 到达
    public function arriveDestinat(){
        $user_id = UserLogic::getInstance()->checkToken();
        $order_id = $this->request->post('order_id/d', 0);
        if (!$order_id) return error_out("", MsgLogic::PARAM_MSG);
        $orderid = OrderModel::getInstance()->orderFind(["id"=>$order_id], "id")["id"] ?: 0;
        if (!$orderid) return error_out("", OrderMsgLogic::ORDER_NOT_EXISTS);
        $result = OrderModel::getInstance()->orderEdit(["id"=>$order_id], ["status"=> 1]);
        if ($result === false) return error_out("", MsgLogic::SERVER_EXCEPTION);
        return success_out("", MsgLogic::SUCCESS);
    }

    // 取消代收款
    public function cancelDsk(){
        $user_id = UserLogic::getInstance()->checkToken();
        $order_id = $this->request->post('order_id/d', 0);
        if (!$order_id) return error_out("", MsgLogic::PARAM_MSG);
        $orderid = OrderModel::getInstance()->orderFind(["id"=>$order_id], "id")["id"] ?: 0;
        if (!$orderid) return error_out("", OrderMsgLogic::ORDER_NOT_EXISTS);
        $result = OrderModel::getInstance()->orderEdit(["id"=>$order_id], ["is_receivables"=> 0]);
        if ($result === false) return error_out("", MsgLogic::SERVER_EXCEPTION);
        return success_out("", MsgLogic::SUCCESS);
    }

    // 检测订单是否被预约
    public function checkOrder(){
        $user_id = UserLogic::getInstance()->checkToken();
        $order_id = $this->request->post('order_id/d', 0);
        if (!$order_id) return error_out("", MsgLogic::PARAM_MSG);
        $order = OrderModel::getInstance()->orderFind(["id"=>$order_id], "id, driver_id");
        if (!$order) return error_out("", OrderMsgLogic::ORDER_NOT_EXISTS);
        $data["status"] = $order["driver_id"] ? 1 : 0;
        return success_out($data, MsgLogic::SUCCESS);
    }








}
