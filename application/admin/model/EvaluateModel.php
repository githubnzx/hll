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

class EvaluateModel extends BaseModel
{
    protected $evaluates = 'evaluates';
    protected $driver    = 'driver';

    const STATUS_DEL = 0;

    public function evaluateDriverList($where = [], $fields = '*', $page = ""){
        $orWhere['e.is_del'] = EvaluateModel::STATUS_DEL;
        $orWhere['d.is_del'] = EvaluateModel::STATUS_DEL;
        return Db::table($this->evaluates)->alias("e")
            ->join("{$this->driver} d", 'd.id = e.driver_id')
            ->field($fields)
            ->where($where)
            ->page($page)
            //->fetchSql(true)
            ->select();
    }

    public function evaluateDriverCount($where = []){
        $orWhere['e.is_del'] = EvaluateModel::STATUS_DEL;
        $orWhere['d.is_del'] = EvaluateModel::STATUS_DEL;
        return Db::table($this->evaluates)->alias("e")
            ->join("{$this->driver} d", 'd.id = e.driver_id')
            ->where($where)
            //->fetchSql(true)
            ->count();
    }

    public function evaluateLevelSum($where = []){
        $where["is_del"] = TruckModel::STATUS_DEL;
        return Db::table($this->evaluates)->where($where)->sum("star_level");
    }

    public function evaluateList($where = [], $fields = '*', $page = ""){
        $where["is_del"] = EvaluateModel::STATUS_DEL;
        return Db::table($this->evaluates)->field($fields)->where($where)->page($page)->select();
    }

    public function evaluateTotal($where = []){
        $where["is_del"] = TruckModel::STATUS_DEL;
        return Db::table($this->evaluates)->where($where)->count();
    }

    public function evaluateFind($where = [], $fields = '*'){
        $where["is_del"] = EvaluateModel::STATUS_DEL;
        return Db::table($this->evaluates)->field($fields)->where($where)->find();
    }

    public function evaluateEdit($where = [], $param){
        if (!isset($param["update_time"])) {
            $param["update_time"] = CURR_TIME;
        }
        return Db::table($this->driver)->where($where)->update($param);
    }



}