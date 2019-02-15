<?php
namespace app\admin\controller;

use app\admin\model\TransferModel;
use app\admin\logic\TransferLogic;
use app\common\logic\MsgLogic;
use app\common\logic\PageLogic;
use app\admin\model\TruckModel;
use app\admin\model\IntegralModel;
use think\Cache;
use think\Config;
use think\Session;
ob_clean();

class Transfer extends Base
{
    // 列表
    public function lst(){
        $name  = request()->post('name/s', '');
        $status= request()->post('status/d', 0);
        $start_time= request()->post('start_time/s', "");
        $start_end = request()->post('start_end/s', "");
        $pageNumber= request()->post('pageNumber/d', 1);
        $pageSize  = request()->post('pageSize/d', 10);
        $pages = $pageNumber . ', ' . $pageSize;
        $where = [];
        if ($name)       $where["d.name"]        = ["like", "%". $name ."%"];
        if ($status)     $where["b.status"]    = $status;
        // 时间范围
        $startTime = strtotime($start_time);
        $startEnd  = strtotime($start_end);
        if ($startTime && $startEnd) {
            $where["b.create_time"] = ["between", [$startTime, $startEnd]];
        } else {
            if ($start_time) $where["b.create_time"] = ["EGT", $startTime];
            if ($start_end) $where["b.create_time"] = ["ELT", $startEnd];
        }
        //$list = IntegralModel::getInstance()->integralList($where, "id, title, integral", $pages) ?: [];
        $field = "b.id, d.name, b.status, b.price";
        $list = TransferModel::getInstance()->billDriverList($where, $field, "b.create_time desc", $pages);
        $total = TransferModel::getInstance()->billDriverTotal($where);
        return json(['total' => $total, 'list' => $list, 'msg' => '']);
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
        //$pay_type = request()->post('pay_type/d', 0); // 1 微信 2 支付宝
        if(!$id) return error_out([], MsgLogic::PARAM_MSG);
        $bill = TransferModel::getInstance()->showBillWFind(['b.id'=>$id], 'b.id, b.driver_id, b.price, w.code, w.type');
        if(!$bill || !$bill['driver_id'] || !$bill['price'] || !$bill['code'] || !$bill["type"]) return error_out([], '提现失败');
        $driver = TransferModel::getInstance()->showDriverFind($bill['driver_id'], 'name, openid');
        if(!$driver || !$driver['name'] || !$driver['openid']) return error_out([], '提现失败');
        //$result = WithdrawalModel::transferWx($id, $bill['code'], $coach['openid'], $bill['price']);
        if ($bill["type"] === 1) {  // 微信
            $order = TransferLogic::getInstance()->transferWx($bill['code'], $driver['openid'], $bill['price'], $bill["type"]);
            if($order['return_code'] != 'SUCCESS' || $order['result_code'] != 'SUCCESS'){
                Log::error('微信提现失败:' . $bill['code'] . '=>' . $order['err_code_des']);
                return error_out('', $order['err_code_des']);
            }
        } else {  // 支付宝
            $order = TransferLogic::getInstance()->refundZfb($bill['code'], $bill['price'], "支付宝提现支付");
            if($order === false) {
                return error_out('', "支付宝提现支付失败");
            }
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
