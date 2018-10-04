<?php
namespace app\user\controller;
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
    /**
     * 函数的含义说明
     *
     * @access  public
     * @author  niuzhenxiang
     * @param mixed token  用户token
     * @return array
     * @date  2018/02/09
     */

    private $truck_type = [1=>"小面包车", 2=>"中面包车", 3=>"小货车", 4=>"大货车"];
    private $truck_param= ["load"=>"吨", "long"=>"长", "wide"=>"宽", "high"=>"高", "cube"=>"方"];

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
        $kilometers  = $this->request->post('kilometers/d', 0);    // 公里数
        if (!$truck_id || !$send_lon || !$send_lat || !$collect_lon || !$collect_lat || !$send_addr || !$collect_addr) {
            return error_out("", "参数错误");
        }
        // 查询货车是否存在
        $trucInfo = TruckModel::getInstance()->truckFind(["id"=>$truck_id], "id, type");
        if(!$trucInfo) return error_out("", OrderMsgLogic::TRUCK_IS_EXISTS);
        if($isReceivables){ // 是预约订单时间必填
            if(!$order_time) return error_out("", OrderMsgLogic::ORDER_IS_RECEIVABLE);
        }
        if(UserLogic::getInstance()->check_name($contacts)) return error_out("", OrderMsgLogic::ORDER_USER_NAME);
        if(UserLogic::getInstance()->check_mobile($phone)) return error_out("", UserLogic::USER_PHONE_MSG);
        // 检测是否未完成订单
        $isExistsOrder = OrderModel::getInstance()->orderFind(["user_id"=>$user_id, "status"=>["in", [0,1]]], "id")["id"] ?: 0;
        if($isExistsOrder) return error_out("", OrderMsgLogic::ORDER_IS_EXISTS);
        // 费用计算
        $price = OrderLogic::getInstance()->imputedPrice($kilometers, $trucInfo["type"], $fee_price);
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
            "fee"     => $fee_price,
            "contacts"=> $contacts,
            "phone"   => $phone,
            "remarks" => $remarks,
            "date"    => strtotime(CURR_DATE),
            "is_place_order" => $order_time ? 1 : 0,
            "order_time" => $order_time ?: strtotime(CURR_DATE)
        ];
        // 下单
        $result = OrderModel::getInstance()->orderInsert($order);
        if($result === false) return error_out("", MsgLogic::SERVER_EXCEPTION);
        return success_out("", MsgLogic::SUCCESS);
    }





}
