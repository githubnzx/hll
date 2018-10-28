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

use app\user\logic\UserLogic;
use app\user\model\TruckModel;
use im\Easemob;
use think\Db;
use think\Log;
use think\Model;

class MemberModel extends BaseModel
{
    protected $tableUser = 'members';
    protected $memberSpecs = 'member_specs';
    protected $memberOrder = 'member_order';
    protected $memberDriver= 'member_driver';


    const STATUS_DEL = 0;
    const USER_TYPE_USER    = 1;

    const MEMBER_USE_STATUS = 1;
    const MEMBER_RECOMMEND  = 1;

    const MEMBER_DEFAULT_TIME = 30;
    const MEMBER_DEFAULT_NUMBER = 2;



    public function memberFind($where, $fields = '*'){
        $where["is_del"] = self::STATUS_DEL;
        $where["status"] = self::MEMBER_USE_STATUS;
        return Db::table($this->tableUser)->field($fields)->where($where)->find();
    }
    // 会员卡列表
    public function memberList($where = [], $fields = '*'){
        $where["is_del"] = self::STATUS_DEL;
        $where["status"] = self::MEMBER_USE_STATUS;
        return Db::table($this->tableUser)->field($fields)->where($where)->order("sort asc")->select();
    }

    // 会员卡规格列表
    public function memberSpecsList($where = [], $fields = '*'){
        $where["is_del"] = self::STATUS_DEL;
        $where["status"] = self::MEMBER_USE_STATUS;
        return Db::table($this->memberSpecs)->field($fields)->where($where)->order("sort asc")->select();
    }

    public function isMember($where, $fields = '*'){
        $where["is_del"] = self::STATUS_DEL;
        $where["status"] = self::MEMBER_USE_STATUS;
        $return = Db::table($this->memberDriver)->field($fields)->where($where)->find();
        if(isset($return["id"]) && $return["id"]) {
            return true;
        } else {
            return false;
        }
    }

    // 会员卡和规格关联查询
    public function memberJoinSpecsFind($where, $field = '*', $sort = ""){
        $where['m.is_del']= self::STATUS_DEL;
        $where['m.status']= self::MEMBER_USE_STATUS;
        $where['m_s.is_del']= self::STATUS_DEL;
        $where['m_s.status']= self::MEMBER_USE_STATUS;
        return Db::table($this->tableUser)
            ->alias('m')
            ->join("$this->memberSpecs m_s", 'm_s.member_id = m.id')
            ->field($field)
            ->where($where)
            ->order($sort)
            ->find();
    }
    // 添加订单返回order_id
    public function memberOrderInsertGetId($data){
        $curr_time = CURR_TIME;
        $data['create_time'] = $curr_time;
        $data['update_time'] = $curr_time;
        return Db::table($this->memberOrder)->insertGetId($data);
    }

    // 添加用户会员
    public function memberDriverAdd($data){
        $curr_time = CURR_TIME;
        $data['create_time'] = $curr_time;
        $data['update_time'] = $curr_time;
        return Db::table($this->memberDriver)->insert($data);
    }

    // 添加订单返回order_id
    public function memberUserFind($where, $fields = "*"){
        $where["is_del"] = self::STATUS_DEL;
        return Db::table($this->memberDriver)->field($fields)->where($where)->find();
    }

    // 修改司机会员
    public function memberUserUp($where, $param){
        if (!isset($param["update_time"])) {
            $param['update_time'] = CURR_TIME;
        }
        return Db::table($this->memberDriver)->where($where)->update($param);
    }

    // 司机会员删除
    public function memberUserDel($id){
        return Db::table($this->memberDriver)->where(["id"=>$id])->update(["is_del"=>1]);
    }

    // 会员订单数据
    public function memberOrderFind($where, $fields = "*"){
        $where["is_del"] = self::STATUS_DEL;
        return Db::table($this->memberOrder)->field($fields)->where($where)->find();
    }

    // 司机支付回调
    public function payDriverMemberSuccess($order, $pay_type){
        Db::startTrans();
        try {
            // 检查司机是否已是会员用户
            $driverMember = $this->memberUserFind(["driver_id"=>$order["driver_id"]], "id, member_id, days, end_time");
            if ($driverMember) { // 已有会员
                if ($driverMember["member_id"] === $order["member_id"]) { // 再次购买会员
                    // 修改司机会员增加期限
                    $validity_day = bcadd($order["validity_day"], $order["give_day"]);
                    $days = bcadd($driverMember["days"], $validity_day);
                    $end_time = bcadd($driverMember["end_time"], $validity_day*24*360);
                    $this->memberUserUp(["id"=>$driverMember["id"], "member_id"=>$driverMember["member_id"]], ["days"=>$days, "end_time"=>$end_time]);
                } else { // 已有会员与再次购买的会员不同
                    $this->memberUserDel($driverMember["id"]); // 删除已有会员
                    // 添加新的会员
                    $data["code"] = "";
                    $data['driver_id']= $order["driver_id"];
                    $data['member_id']= $order["member_id"];
                    $data['title']    = $order["title"];
                    $data['type']     = $order["type"];
                    $data['back_img'] = $order["style"];
                    $data['limit_second'] = $order["limit_second"];
                    $data['up_limit_number'] = $order["up_limit_number"];
                    $data['days'] = bcadd($order["validity_day"], $order["give_day"]);
                    $data['end_time'] = bcadd(CURR_TIME, bcadd($order["validity_day"], $order["give_day"]));
                    $data['rights'] = $order["rights"];
                    $data['notes'] = $order["notes"];
                    $this->memberDriverAdd($data);
                }
            } else {
                // 添加新的会员
                $data["code"] = "";
                $data['driver_id']= $order["driver_id"];
                $data['member_id']= $order["member_id"];
                $data['title']    = $order["title"];
                $data['type']     = $order["type"];
                $data['back_img'] = $order["back_img"];
                $data['limit_second'] = $order["limit_second"];
                $data['up_limit_number'] = $order["up_limit_number"];
                $data['days'] = bcadd($order["validity_day"], $order["give_day"]);
                $data['end_time'] = bcadd(CURR_TIME, bcadd($order["validity_day"], $order["give_day"]));
                $data['rights'] = $order["rights"];
                $data['notes'] = $order["notes"];
                $this->memberDriverAdd($data);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
        return true;
    }



}