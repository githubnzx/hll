<?php
namespace app\driver\model;

use think\Db;
use think\Log;
use think\Model;
use think\Cache;

class DepositMode extends BaseModel
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

    public function depositOrderFind($where, $field = "*"){
        $where['is_del'] = self::STATUS_DEL;
        return Db::table($this->depositOrder)->field($field)->where($where)->find();
    }

    public function depositOrderUp($where, $param){
        if(!isset($param["update_time"])){
            $param["update_time"] = CURR_TIME;
        }
        return Db::table($this->depositOrder)->where($where)->update($param);
    }


    // 修改deposit_order
    public function depositOrderEdit($order, $pay_type){
        Db::startTrans();
        try {
            $this->depositOrderUp(["id"=>$order["id"]], ["status"=>3]);
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }

}