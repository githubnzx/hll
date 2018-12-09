<?php
namespace app\admin\controller;
use app\admin\model\TransferModel;
use app\common\logic\MsgLogic;
use app\common\logic\PageLogic;
use app\admin\model\TruckModel;
use app\admin\model\IntegralModel;
use think\Cache;
use think\Config;
use think\Session;


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
        if ($name)       $where["d.name"]        = ["like", "%". $name ."%"];;
        if ($status)     $where["b.status"]    = $status;
        // 时间范围
        if ($start_time && $start_end) {
            $where["b.create_time"] = ["between", [$start_time, $start_end]];
        } else {
            if ($start_time) $where["b.create_time"] = ["EGT", $start_time];
            if ($start_end) $where["b.create_time"] = ["ELT", $start_end];
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