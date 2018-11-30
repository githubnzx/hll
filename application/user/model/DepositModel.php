<?php
namespace app\user\model;

use think\Db;
use think\Log;
use think\Model;
use think\Cache;

class DepositModel extends BaseModel
{
    protected $depositOrder = 'deposit_order';

    const STATUS_DEL = 0;
    const USER_TYPE_USER = 1;
    const OPERATE_TYPE_JYJ = 1;
    const OPERATE_TYPE_TYJ = 2;

    public function depositOrderAddGetId($data){
        $curr_time = CURR_TIME;
        $data['create_time'] = $curr_time;
        $data['update_time'] = $curr_time;
        return Db::table($this->depositOrder)->insertGetId($data);
    }

    public function depositOrderFind($where, $fields = "*"){
        $where["is_del"] = self::STATUS_DEL;
        return Db::table($this->depositOrder)->field($fields)->where($where)->find();
    }

    public function depositOrderUp($where, $fields = "*"){
        if(!isset($param["update_time"])){
            $param["update_time"] = CURR_TIME;
        }
        return Db::table($this->tableUser)->where($where)->update($param);
    }

    public function orderPaySuccess($order, $pay_type){
        Db::startTrans();
        try {
            $this->depositOrderUp(["id"=>$order["id"]], ["status"=>2]);
            $depositNumber  = UsersModel::getInstance()->userFind(["id"=>$order["user_id"]], "deposit_number")["deposit_number"] ?: 0;
            $deposit_number = bcadd($depositNumber, 1);
            UsersModel::getInstance()->userEdit(["id"=>$order["user_id"]], ["deposit_status"=>1, "price"=>$order["price"], "deposit_number"=>$deposit_number]);
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }

}