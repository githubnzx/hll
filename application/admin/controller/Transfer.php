<?php
namespace app\admin\controller;
use app\admin\model\TransferModel;
use app\common\logic\MsgLogic;
use app\common\logic\PageLogic;
use app\admin\model\TruckModel;
use app\admin\model\IntegralModel;
use think\Cache;
use think\Config;


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
        $result = TransferModel::editBillBalance($id);
        if(!$result) return error_out('', MsgLogic::SERVER_EXCEPTION);
        return success_out("", MsgLogic::SUCCESS);
    }
    // 通过
    public static function transfer(){
        $id = request()->post('id/d', 0);
        if(!$id) return error_out([], MsgLogic::PARAM_MSG);
        $bill = TransferModel::getInstance()->showBillWFind(['b.id'=>$id], 'b.id,b.driver_id, b.price, w.code');
        if(!$bill || !$bill['driver_id'] || !$bill['price'] ||! $bill['code']) return error_out([], '提现失败');
        $driver = TransferModel::getInstance()->showDriverFind($bill['driver_id'], 'title, openid');
        if(!$driver || !$driver['title'] || !$driver['openid']) return error_out([], '提现失败');
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












    // 积分详情
    public function info(){
        $good_id = $this->request->post('good_id/d', 0);
        if(!$good_id) return error_out("", MsgLogic::PARAM_MSG);
        $integralInfo = IntegralModel::getInstance()->integralFind(["id"=>$good_id], "id, title, integral, surplus_number") ?: [];
        $imageArr = [];
        $images = TruckModel::getInstance()->certList(["main_id"=>$integralInfo["id"], "type"=> IntegralModel::CERT_TYPE], "img");
        foreach ($images as $ks => $vs){
            $imageArr[] = $vs["img"];
        }
        $integralInfo["images"] = $imageArr;
        return success_out($integralInfo);
    }

    // 编辑
    public function edit(){
        $good_id = $this->request->post('good_id/d', 0);
        $data["title"]   = $this->request->post('title/s', "");
        $data["number"]  = $this->request->post('number/s', "");
        $data["surplus_number"]= $this->request->post('surplus_number/s', "");
        $data["integral"] = $this->request->post('integral/s', "");
        $image = $this->request->post('image/arr', []);
        if(!$good_id) return error_out("", MsgLogic::PARAM_MSG);
        foreach ($data as $key => $val) {
            if (empty($val)) unset($data[$key]);
        }
        $order = IntegralModel::getInstance()->integralGoodEdit($good_id, $data, $image);
        if($order === false) return error_out("", MsgLogic::SERVER_EXCEPTION);
        return success_out("", MsgLogic::SUCCESS);
    }

    // 添加
    public function add(){
        $data["title"]   = $this->request->post('title/s', "");
        $data["number"]  = $this->request->post('number/d', 0);
        $data["surplus_number"]= $this->request->post('surplus_number/d', 0);
        $data["integral"] = $this->request->post('integral/s', "");
        $image = $this->request->post('image/a', []);
        if(!$data["title"] || !$data["number"] || !$data["surplus_number"] || !$data["integral"]) return error_out("", MsgLogic::PARAM_MSG);
        $order = IntegralModel::getInstance()->integralGoodAdd($data, $image);
        if($order === false) return error_out("", MsgLogic::SERVER_EXCEPTION);
        return success_out("", MsgLogic::SUCCESS);
    }

    // 删除
    public function del(){
        $id = $this->request->post('good_id/s', "");
        if(!$id) return error_out("", MsgLogic::PARAM_MSG);
        $result = IntegralModel::getInstance()->integralEdit(["id"=>$id], ["is_del"=>1]);
        if($result === false) return error_out("", MsgLogic::SERVER_EXCEPTION);
        return success_out("", MsgLogic::SUCCESS);
    }





}
