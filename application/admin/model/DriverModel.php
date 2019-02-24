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

use app\common\sms\DriverSms;
use app\admin\model\UserModel;
use app\common\model\ServiceReceiveModel;
use app\common\push\Push;
use im\Easemob;
use think\Db;
use think\Log;
use think\Model;

class DriverModel extends BaseModel
{
    protected $driver    = 'driver';

    const STATUS_DEL = 0;

    public function driverList($where = [], $fields = '*', $page = "", $order = ""){
        $orWhere['is_del'] = EvaluateModel::STATUS_DEL;
        return Db::table($this->driver)->field($fields)->where($where)->order($order)->page($page)->select();
    }

    public function driverCount($where = []){
        $orWhere['is_del'] = EvaluateModel::STATUS_DEL;
        return Db::table($this->driver)->where($where)->count();
    }

    public function driverFind($where, $fields = "*"){
        $orWhere['is_del'] = EvaluateModel::STATUS_DEL;
        return Db::table($this->driver)->where($where)->field($fields)->find();
    }

    public function driverEdit($where, $param){
        if (!isset($param["update_time"])) {
            $param["update_time"] = CURR_TIME;
        }
        return Db::table($this->driver)->where($where)->update($param);
    }

    public function driverAuditEdit($driver_id, $audit_status, $phone){
        Db::startTrans();
        try {
            $user_id = $this->driverEdit(["id"=>$driver_id], ["audit_status"=>$audit_status]);
            // 推送
            if ($audit_status === 2) { //通过 推送
                $msg_title = "";
                $msg_content = '';
            } elseif ($audit_status === 3) { // 拒绝 推送
                $msg_title = "";
                $msg_content = '';
            }
            //$ext = CommonConfig::getPushExt(CommonConfig::getRouteById(cServiceReceiveModel::ROUTE_ORDER_MANAGE));
            Push::getInstance()->pushMsgAll($driver_id, ServiceReceiveModel::USER_TYPE_DRIVER, $msg_title, $msg_content);
            // 发短信
            if ($phone) {
                $response = DriverSms::auditPass($phone);
                if ($response->Code != 'OK') {
                    return false;
                }
            }
            Db::commit();
            return $user_id;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }


}