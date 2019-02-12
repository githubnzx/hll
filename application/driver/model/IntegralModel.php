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
namespace app\driver\model;

use app\driver\model\DriverModel;
use app\user\logic\UserLogic;
use im\Easemob;
use think\Db;
use think\Log;
use think\Model;
use think\Cache;

class IntegralModel extends BaseModel
{
    protected $integralGoodsTable = 'integral_goods';
    protected $userIntegralRecord = 'integral_record';
    protected $userIntegral       = 'integral';
    protected $integralOrderTable = 'integral_order';
    protected $userIntegralGood   = 'user_integral_goods';

    const STATUS_DEL = 0;
    const CERT_TYPE  = 9;//4

    public function integralList($where = [], $fields = '*'){
        $where["is_del"] = IntegralModel::STATUS_DEL;
        return Db::table($this->integralGoodsTable)->field($fields)->where($where)->select();
    }

    public function integralFind($where = [], $fields = '*'){
        $where["is_del"] = IntegralModel::STATUS_DEL;
        return Db::table($this->integralGoodsTable)->field($fields)->where($where)->find();
    }

    public function integralOrderInsert($data){
        $data['create_time'] = CURR_TIME;
        $data['update_time'] = CURR_TIME;
        return Db::table($this->integralOrderTable)->insert($data);
    }

    public function integralOrderAdd($data){
        Db::startTrans();
        try {
            // 添加积分兑换商品订单
            $this->integralOrderInsert($data);
            // 添加用户和积分商品关联数据
            $this->userIntegralGoodAdd(["user_id"=>$data["user_id"],"goods_id"=>$data["goods_id"], "user_type"=>DriverModel::USER_TYPE_USER]);
            // redis 减去一个商品
            if (Cache::store('integral')->has('goods_id:' . $data["goods_id"])) Cache::store('integral')->dec('goods_id:' . $data["goods_id"]);
            // 删除积分商城表中的剩余数量
            Db::table($this->integralGoodsTable)->where('id', $data["goods_id"])->setDec("surplus_number", 1);
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }

    public function userIntegralRecordInsert($data){
        $data['create_time'] = CURR_TIME;
        $data['update_time'] = CURR_TIME;
        return Db::table($this->userIntegralRecord)->insert($data);
    }

    public function userIntegralAdd($data){
        $data['create_time'] = CURR_TIME;
        $data['update_time'] = CURR_TIME;
        return Db::table($this->userIntegral)->insert($data);
    }

    public function integralOrderSelect($where, $fields = "*", $page){
        $where["o.is_del"] = IntegralModel::STATUS_DEL;
        return Db::table($this->integralOrderTable)
            ->alias('o')
            ->join("{$this->integralGoodsTable} g", 'g.id = o.goods_id')
            ->field($fields)
            ->where($where)
            ->page($page)
            //->fetchSql(true)
            ->select() ?: [];
    }

    public function integralOrderFind($where, $fields = "*"){
        $where["is_del"] = IntegralModel::STATUS_DEL;
        return Db::table($this->integralOrderTable)->field($fields)->where($where)->find();
    }

    public function integralOrderEdit($where, $param){
        if(!isset($param["update_time"])){
            $param["update_time"] = CURR_TIME;
        }
        return Db::table($this->integralOrderTable)->where($where)->update($param);
    }



    public function userIntegralFind($where, $field = "*"){
        $where["is_del"] = IntegralModel::STATUS_DEL;
        return Db::table($this->userIntegral)->field($field)->where($where)->find();
    }

    public function userIntegralRecordList($where, $field = "*"){
        return Db::table($this->userIntegralRecord)->field($field)->where($where)->select() ?: [];
    }

    public function userIntegralGoodFind($where, $fields = "*"){
        return Db::table($this->userIntegralGood)->field($fields)->where($where)->find();
    }
    public function userIntegralGoodAdd($data){
        $data["create_time"] = CURR_TIME;
        $data["update_time"] = CURR_TIME;
        return Db::table($this->userIntegralGood)->insert($data);
    }


}