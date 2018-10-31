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

class TransferModel extends BaseModel
{
    protected $bill = 'bill';
    protected $billWithdraw = 'bill_withdraw';
    protected $driver = 'driver';

    const STATUS_DEL = 0;
    const CERT_TYPE  = 4;

    public function billDriverTotal($where){
        $where['b.is_del'] = self::IS_SHOW;
        $where['d.is_del'] = self::IS_SHOW;
        $where['b.type_status'] = 1;
        return  Db::table($this->driver)->alias('d')
            ->join($this->bill . ' b', ' d.id = b.driver_id', 'left')
            ->where($where)
            ->count();
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
        return Db::table(self::$balanceName)->where($where)->setInc('balance', $price);
    }

    public function showBillFind($id, $fields = '*'){
        return Db::table($this->bill)->field($fields)->where(['id'=>$id, 'is_del'=>0])->find();
    }

    public function editBillBalance($id){
        Db::startTrans();
        try {
            $billInfo = self::showBillFind($id, 'price, driver_id');
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
        $where['b.is_del'] = self::IS_DEL;
        $where['w.is_del'] = self::IS_DEL;
        return Db::table($this->bill)
            ->alias('b')
            ->join($this->billWithdraw . ' w', 'w.bill_id = b.id', 'left')
            ->where($where)
            ->field($fields)
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


}