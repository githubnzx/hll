<?php
// +----------------------------------------------------------------------
// | snake
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 http://baiyf.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: NickBai <1902822973@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\model;

use app\admin\model\UserModel;
use im\Easemob;
use think\Db;
use think\Log;
use think\Model;
use think\Session;

class OrderModel extends BaseModel
{
    protected $order = 'orders';
    protected $users = 'users';
    protected $truck = 'truck';
    protected $driver = 'driver';

    const STATUS_DEL = 0;
    const CERT_TYPE  = 4;

    // 表
    private function orderTable(){
        $where["o.is_del"] = self::STATUS_DEL;
        $where["t.is_del"] = self::STATUS_DEL;
        $where["u.is_del"] = self::STATUS_DEL;
        $where["d.is_del"] = self::STATUS_DEL;
        return Db::table($this->order)->alias('o')
            ->join($this->users  . ' u', 'u.id = o.user_id',   'left')
            ->join($this->driver . ' d', 'd.id = o.driver_id', 'left')
            ->join($this->truck  . ' t', 't.id = o.truck_id',  'left')
            ->where($where);
    }

    // 表
    private function ordersTable(){
        $where["o.is_del"] = self::STATUS_DEL;
        $where["t.is_del"] = self::STATUS_DEL;
        $where["u.is_del"] = self::STATUS_DEL;
        //$where["d.is_del"] = self::STATUS_DEL;
        return Db::table($this->order)->alias('o')
            ->join($this->users  . ' u', 'u.id = o.user_id',   'left')
            //->join($this->driver . ' d', 'd.id = o.driver_id', 'left')
            ->join($this->truck  . ' t', 't.id = o.truck_id',  'left')
            ->where($where);
    }

    // 订单列表和相关数据
    public function ordersList($data){
        $where = isset($data["where"]) && !empty($data["where"]) ? $data["where"] : [];
        $field = isset($data["field"]) && !empty($data["field"]) ? $data["field"] : "*";
        $pages = isset($data["page"])  && !empty($data["page"])  ? $data["page"] : "";
        $order = isset($data["order"]) && !empty($data["order"]) ? $data["order"] : "";
        return $this->ordersTable()->where($where)->field($field)->order($order)->page($pages)->select();
    }
    // 订单列表和相关数据 详情
    public function ordersInfo($data){
        $where = isset($data["where"]) && !empty($data["where"]) ? $data["where"] : [];
        $field = isset($data["field"]) && !empty($data["field"]) ? $data["field"] : "*";
        //$pages = isset($data["page"])  && !empty($data["page"])  ? $data["page"] : "";
        $order = isset($data["order"]) && !empty($data["order"]) ? $data["order"] : "";
        return $this->orderTable()->where($where)->field($field)->order($order)->find();
    }

    public function ordersTotal($data){
        $where = isset($data["where"]) && !empty($data["where"]) ?: [];
        return $this->orderTable()->where($where)->count();
    }

    public function billDriverList($where, $field = "*", $order = "", $pages = ""){
        $where['b.is_del'] = self::IS_SHOW;
        $where['d.is_del'] = self::IS_SHOW;
        $where['b.type_status'] = 1;
        return  Db::table($this->driver)->alias('d')
            ->join($this->bill . ' b', ' d.id = b.driver_id', 'left')
            //->join(self::$withdrawName . ' w', 'w.bill_id = b.id', 'left')
            ->field($field)
            ->where($where)
            ->order($order)
            ->page($pages)
            //->fetchSql(true)
            ->select();
    }
    public function billEdit($where, $param){
        if (!isset($param['update_time'])) {
            $param['update_time'] = CURR_TIME;
        }
        return Db::table($this->bill)->where($where)->update($param);
    }

    public function editWithdraw($where, $param = []){
        if (!isset($param['update_time'])) {
            $param['update_time'] = CURR_TIME;
        }
        return Db::table($this->billWithdraw)->where($where)->update($param);
    }

    public function editBalance($where, $price){
        return Db::table($this->balance)->where($where)->setInc('balance', $price);
    }

    public function showBillFind($id, $fields = '*'){
        return Db::table($this->bill)->field($fields)->where(['id'=>$id, 'is_del'=>0])->find();
    }

    public function editBillBalance($id, $billInfo){
        Db::startTrans();
        try {
            $this->billEdit(['id'=>$id], ['status' => 3, 'tag'=>'提现失败']);//balance
            $this->editWithdraw(['bill_id'=>$id], ['title'=>Session::get('username'), 'audit_time'=>CURR_TIME]);
            $this->editBalance(['user_id'=>$billInfo['driver_id'], 'user_type'=>2, 'is_del'=>0], $billInfo['price']);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
        return true;
    }

    public function showDriverFind($id, $fields = '*'){
        return Db::table($this->driver)->field($fields)->where(['id'=>$id, 'is_del'=>0])->find();
    }

    public function showBillWFind($where, $fields = '*'){
        $where['b.is_del'] = self::STATUS_DEL;
        $where['w.is_del'] = self::STATUS_DEL;
        return Db::table($this->bill)
            ->alias('b')
            ->join($this->billWithdraw . ' w', 'w.bill_id = b.id', 'left')
            ->where($where)
            ->field($fields)
            //->fetchSql(true)
            ->find();
    }

    public function editBillWithdraw($bill, $withdraw){
        Db::startTrans();
        try {
            $this->billEdit($bill);
            $this->editWithdraw(['bill_id'=>$bill['id']], $withdraw);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
        return true;
    }

    public function orderCount($where){
        $where["is_del"] = self::STATUS_DEL;
        return Db::table($this->order)->where($where)->count();
    }

    public function orderTotalPrice($where){
        $where["is_del"] = self::STATUS_DEL;
        return Db::table($this->order)->where($where)->sum("total_price");
    }


}