<?php
namespace app\admin\controller;

use app\admin\model\OrderModel;
use app\admin\model\TransferModel;
use app\common\config\DriverConfig;
use app\common\logic\MsgLogic;
use app\common\logic\PageLogic;
use app\admin\logic\OrderLogic;
use app\admin\model\TruckModel;
use app\admin\model\IntegralModel;
use think\Cache;
use think\Config;
use think\Session;


ob_clean();

class Order extends Base
{
    // 列表
    public function lst(){
        $code  = request()->post('code/s', '');
        $status= request()->post('status/d', 0);
        $phone = request()->post('phone/d', 0);
        $truck_type = request()->post('truck_type/d', 0);
        $pageNumber= request()->post('pageNumber/d', 1);
        $pageSize  = request()->post('pageSize/d', 10);
        $pages = $pageNumber . ', ' . $pageSize;
        $where = [];
        if ($code)   $where["o.code"]   = $code;
        if ($status) $where["o.status"] = $status;
        if ($phone)  $where["u.phone"]  = $phone;
        if ($truck_type)  $where["t.type"]  = $truck_type;
//        $field = "o.id, o.order_time, o.status, o.send_good_addr, o.collect;_good_addr, o.total_price, o.contacts, o.phone contact_number, o.remarks, u.name user_name, u.phone user_phone, u.addr_info user_addr_info, d.name driver_name, d.phone driver_phone, t.type";
        $field = "o.id, o.order_time, o.status, o.send_good_addr, o.collect_good_addr, o.total_price, o.contacts, o.phone contact_number, o.remarks, u.name user_name, u.phone user_phone, u.addr_info user_addr_info, o.contacts driver_name, o.phone driver_phone, t.type";
        $data["where"] = $where;
        $data["field"] = $field;
        $data["page"]  = $pages;
        $data["order"] = "o.order_time desc";
        $list = OrderModel::getInstance()->ordersList($data) ?: [];
        foreach ($list as $key => &$value) {
            $value["total_price"] = handlePrice($value["total_price"]);
            $value["order_time"] = date("Y-m-d H:i:s", $value["order_time"]);
            $value["status"]= OrderLogic::getInstance()->handleStatus($value["status"]);
            $value["type"]  = DriverConfig::getInstance()->truckTypeNameId($value["type"]);
        }
        $total = OrderModel::getInstance()->ordersTotal($data["where"]);
        return json(['total' => $total, 'list' => $list, 'msg' => '']);
    }

    // 详情
    public function info(){
        $order_id  = request()->post('order_id/d', 0);
        $data["where"] = ["o.id"=>$order_id];
        $data["field"] = "o.id, o.order_time, o.status, o.send_good_addr, o.collect_good_addr, o.total_price, o.contacts, o.phone contact_number, o.remarks, u.name user_name, u.phone user_phone, u.addr_info user_addr_info, d.name driver_name, d.phone driver_phone, t.type";
        $info = OrderModel::getInstance()->ordersInfo($data) ?: [];
        if ($info) {
            $info["total_price"] = handlePrice($info["total_price"]);
            $info["order_time"] = date("Y-m-d H:i:s", $info["order_time"]);
            $info["status"]= OrderLogic::getInstance()->handleStatus($info["status"]);
            $info["type"]  = DriverConfig::getInstance()->truckTypeNameId($info["type"]);
        }
        $total = OrderModel::getInstance()->ordersTotal($data["where"]);
        return json(['total' => $total, 'list' => $info, 'msg' => '']);
    }

    // 拒绝
    public function refuse(){
        $id = request()->post('id/d', 0);
        if(!$id) error_out([], MsgLogic::PARAM_MSG);
        $billInfo = TransferModel::getInstance()->showBillFind($id, 'status, price, driver_id');
        if ($billInfo["status"] !== 1) return error_out("", "拒绝失败");
        $result = TransferModel::getInstance()->editBillBalance($id, $billInfo);
        if(!$result) return error_out('', MsgLogic::SERVER_EXCEPTION);
        return success_out("", MsgLogic::SUCCESS);
    }
    // 通过
    public static function transfer(){
        $id = request()->post('id/d', 0);
        if(!$id) return error_out([], MsgLogic::PARAM_MSG);
        $bill = TransferModel::getInstance()->showBillWFind(['b.id'=>$id], 'b.id, b.driver_id, b.price, w.code');
        if(!$bill || !$bill['driver_id'] || !$bill['price'] ||! $bill['code']) return error_out([], '提现失败');
        $driver = TransferModel::getInstance()->showDriverFind($bill['driver_id'], 'name, openid');
        if(!$driver || !$driver['name'] || !$driver['openid']) return error_out([], '提现失败');
        //$result = WithdrawalModel::transferWx($id, $bill['code'], $coach['openid'], $bill['price']);
        $order = TransferLogic::getInstance()->transferWx($bill['code'], $driver['openid'], $bill['price']);
        if($order['return_code'] != 'SUCCESS' || $order['result_code'] != 'SUCCESS'){
            return error_out('', $order['err_code_des']);
        }
        $bill['id'] = $id;
        $bill['status'] = 2;
        $bill['tag'] = '完成';
        unset($bill['code']);
        $withdraw['title'] = Session::get('username');
        $withdraw['audit_time'] = CURR_TIME;
        $result = TransferModel::getInstance()->editBillWithdraw($bill, $withdraw);
        if(!$result) return error_out('', MsgLogic::SERVER_EXCEPTION);
        return success_out("", MsgLogic::SUCCESS);
    }



}
