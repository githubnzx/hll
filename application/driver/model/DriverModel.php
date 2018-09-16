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

class DriverModel extends BaseModel
{
    protected $tableUser = 'driver';
    protected $wechatTable = 'wechat';
    protected $certTable   = 'cert';


    const STATUS_DEL = 0;
    const USER_TYPE_USER    = 2;
    const INTEGRAL_REGISTER = 10;
    const TYPE_IN  = 1;
    const TYPE_OUT = 2;

    const CARD_JUST = 4;
    const CARD_BACK = 5;
    const JS_CARD   = 6;
    const XS_CARD   = 7;
    const DRIVER_CAR  = 8;


    public function userFind($where, $fields = '*'){
        $result = Db::table($this->tableUser)->field($fields)->where($where)->find() ?: [];
        if(isset($result["icon"]) && $result["icon"]){
            $result["icon"] = handleImgPath($result["icon"]);
        }
        return $result;
    }

    public function userBindAll($where, $fields = '*'){
        return Db::table($this->tableUser)->field($fields)->where($where)->select();
    }

    public function userEdit($where, $param){
        if(!isset($param["update_time"])){
            $param["update_time"] = CURR_TIME;
        }
        return Db::table($this->tableUser)->where($where)->update($param);
    }

    public function userInsert($data){
        $user['create_time'] = CURR_TIME;
        $user['update_time'] = CURR_TIME;
        return Db::table($this->tableUser)->insertGetId($data);
    }

    // 用户注册
    public function userAdd($data){
        Db::startTrans();
        try {
            $user_id = $this->userInsert($data);
            // 我的积分
            IntegralModel::getInstance()->userIntegralAdd(["user_id"=>$user_id, "integral"=>DriverModel::INTEGRAL_REGISTER, "user_type"=>DriverModel::USER_TYPE_USER]);
            // 积分记录
            $record["user_id"] = $user_id;
            $record["integral"]= DriverModel::INTEGRAL_REGISTER;
            $record["user_type"]= DriverModel::USER_TYPE_USER;
            $record["type"]    = DriverModel::TYPE_IN;
            $record["operation_type"] = 1;
            $record["tag"] = "用户注册";
            IntegralModel::getInstance()->userIntegralRecordInsert($record);
            Db::commit();
            return $user_id;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }

    public function wechatFind($where, $field = "*"){
        $where["is_del"] = DriverModel::USER_TYPE_USER;
        return Db::table($this->wechatTable)->field($field)->where($where)->find();
    }

    public function wxInsert($data){
        $data['type'] = DriverModel::USER_TYPE_USER;
        $data['create_time'] = CURR_TIME;
        $data['update_time'] = CURR_TIME;
        return Db::table($this->wechatTable)->insertGetId($data);
    }

    public function wechatUpdate($where, $param){
        $param['update_time'] = CURR_TIME;
        return Db::table($this->wechatTable)->where($where)->update($param);
    }

    public function userWechatFind($wechat_id, $data){
        Db::startTrans();
        try {
            $user_id = $this->userAdd($data);
            $this->wechatUpdate(["id"=>$wechat_id], ["user_id"=>$user_id]);
            Db::commit();
            return $user_id;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }

    // 添加照片
    public function addCert($data){
        return Db::table($this->certTable)->insertAll($data);
    }

    // 完善信息
    public function userPerfectInfoEdit($user_id, $data, $photo){
        Db::startTrans();
        try {
            $cart = [];
            $user_id = $this->userEdit(["id"=>$user_id], $data);
            if(!$user_id) return false;
            // 身份证 正面
            if(isset($photo["id_card"]["just"]) && $photo["id_card"]["just"]){
                $_cart["main_id"] = $user_id;
                $_cart["img"]  = $photo["id_card"]["just"];
                $_cart["type"] = DriverModel::CARD_JUST;
                $cart[] = $_cart;
            }
            // 身份证 反面
            if(isset($photo["id_card"]["back"]) && $photo["id_card"]["back"]){
                $_cart["main_id"] = $user_id;
                $_cart["img"]  = $photo["id_card"]["back"];
                $_cart["type"] = DriverModel::CARD_BACK;
                $cart[] = $_cart;
            }
            // 驾驶证
            if(isset($photo["js_cert"]) && $photo["js_cert"]){
                $_cart["main_id"] = $user_id;
                $_cart["img"]  = $photo["js_cert"];
                $_cart["type"] = DriverModel::JS_CARD;
                $cart[] = $_cart;
            }
            // 行驶证
            if(isset($photo["xs_cert"]) && $photo["xs_cert"]){
                $_cart["main_id"] = $user_id;
                $_cart["img"]  = $photo["xs_cert"];
                $_cart["type"] = DriverModel::XS_CARD;
                $cart[] = $_cart;
            }
            // 行驶证
            if(isset($photo["car"]) && $photo["car"]){
                foreach ($photo["car"] as $key => $val) {
                    $_cart["main_id"] = $user_id;
                    $_cart["img"] = $val;
                    $_cart["type"] = DriverModel::DRIVER_CAR;
                    $cart[] = $_cart;
                }
            }
            if($cart) $this->addCert($cart);
            Db::commit();
            return $user_id;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }
}