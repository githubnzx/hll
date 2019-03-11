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

    public function integralList($where = [], $fields = '*', $page = ""){
        $where["is_del"] = IntegralModel::STATUS_DEL;
        return Db::table($this->integralGoodsTable)->field($fields)->where($where)->page($page)->select();
    }
    public function integralTotal($where = []){
        $where["is_del"] = IntegralModel::STATUS_DEL;
        return Db::table($this->integralGoodsTable)->where($where)->count();
    }

    public function integralFind($where = [], $fields = '*'){
        $where["is_del"] = IntegralModel::STATUS_DEL;
        return Db::table($this->integralGoodsTable)->field($fields)->where($where)->find();
    }

    public function integralAddGetId($data){
        $data['create_time'] = CURR_TIME;
        $data['update_time'] = CURR_TIME;
        return Db::table($this->integralGoodsTable)->insertGetId($data);
    }

    public function integralAdd($data){
        $data['create_time'] = CURR_TIME;
        $data['update_time'] = CURR_TIME;
        return Db::table($this->integralGoodsTable)->insert($data);
    }

    public function integralEdit($where, $param){
        if(!isset($param["update_time"])){
            $param["update_time"] = CURR_TIME;
        }
        return Db::table($this->integralGoodsTable)->where($where)->update($param);
    }


    public function integralGoodEdit($goodId, $data, $image){
        Db::startTrans();
        try {
            $this->integralEdit(["id"=>$goodId], $data);
            // 图片
            if (isset($image["add"])){
                $cert = [];
                $imageArrAdd = explode(",", $image);
                foreach ($imageArrAdd as $key => $val) {
                    $dataImg["type"] = TruckModel::CERT_TYPE;
                    $dataImg["img"] = $val;
                    $dataImg["main_id"] = $goodId;
                    $dataImg["create_time"] = CURR_TIME;
                    $dataImg["update_time"] = CURR_TIME;
                    $cert[] = $dataImg;
                }
                if ($cert) TruckModel::getInstance()->integralAdd($cert);
            }
            if (isset($image["del"])){
                $imageArrDel = explode(",", $image["del"]);
                // 删除表
                $goodList = $this->integralList(["id"=>["in", $imageArrDel]]);
                // 服务器img文件
                foreach ($goodList as $key => $value) {
                    $base_dir = Config::get('img.path');
                    if (file_exists($base_dir.DS.$value)) {
                        unlink($base_dir.DS.$value);
                    }
                }
                TruckModel::getInstance()->integralEdit(["id"=>["in", $imageArrDel]], ["is_del"=>1]);
            }
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }

    public function integralGoodAdd($data, $image){
        Db::startTrans();
        try {
            $goodId = $this->integralAddGetId($data);
            if ($image){
                $cert = [];
                //$imageArr = implode(",", $image);
                foreach ($image as $key => $val) {
                    $dataImg["type"] = self::CERT_TYPE; //商品照片
                    $dataImg["img"] = $val;
                    $dataImg["main_id"] = $goodId;
                    $dataImg["create_time"] = CURR_TIME;
                    $dataImg["update_time"] = CURR_TIME;
                    $cert[] = $dataImg;
                }
                if ($cert) TruckModel::getInstance()->certAddAll($cert);
            }
            //echo 111;die;
            // 添加redis
            Cache::store('integral')->set('goods_id:' . $goodId, $data["surplus_number"]);
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

    // 修改
    public function integralSetDec($where, $integral){
        return Db::table($this->userIntegral)->where($where)->setDec("integral", $integral);
    }

    // 同过
    public function integralPass($order_id){
        Db::startTrans();
        try {
            // select
            $integralOrder = $this->integralOrderFind(["id"=>$order_id], "user_id, user_type, integral");
            if(!$integralOrder) return false;
            $this->integralSetDec(["user_id"=>$integralOrder["user_id"], "user_type"=>$integralOrder["user_type"]], $integralOrder["integral"]);
            // 修改订单状态
            $this->integralOrderEdit(["id"=>$order_id], ["status"=>2]);
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }

    // 拒绝
    public function integralRefuse($order_id){
        Db::startTrans();
        try {
            // select
            $integralOrder = $this->integralOrderFind(["id"=>$order_id], "user_id, user_type, integral, goods_id");
            if(!$integralOrder) return false;
            // del用户和积分商品关联数据
            Db::table($this->userIntegralGood)->where(["user_id"=>$integralOrder["user_id"],"goods_id"=>$integralOrder["goods_id"], "user_type"=>$integralOrder["user_type"]])->delete();
            // redis 加一个商品
            if (Cache::store('integral')->has('goods_id:' . $integralOrder["goods_id"])) Cache::store('integral')->inc('goods_id:' . $integralOrder["goods_id"]);
            // 加积分商城表中的剩余数量
            Db::table($this->integralGoodsTable)->where('id', $integralOrder["goods_id"])->setInc("surplus_number", 1);
            // 修改订单状态
            $this->integralOrderEdit(["id"=>$order_id], ["status"=>4]);
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
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