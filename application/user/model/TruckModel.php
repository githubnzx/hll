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
namespace app\user\model;

use app\admin\model\UserModel;
use app\user\logic\UserLogic;
use im\Easemob;
use think\Db;
use think\Log;
use think\Model;

class TruckModel extends BaseModel
{
    protected $tableUser = 'truck';
    protected $certTable = 'cert';

    const STATUS_DEL = 0;
    const CERT_TYPE  = 1;

    public function truckList($where = [], $fields = '*'){
        return Db::table($this->tableUser)->field($fields)->where($where)->select();
    }

    public function certList($where, $fields = "*"){
        $result = Db::table($this->certTable)->field($fields)->where($where)->select() ?: [];
        foreach ($result as $key => $value){
            if($value["img"]) $result[$key]["img"] = handleImgPath($value["img"]);
        }
        return $result;
    }














    // ----------------
/*
    public static function userAdd($date) {
        return Db::table(self::$table)->insert($date);
    }

    public static function register($user)
    {
        return  Db::table(self::$table)->insertGetId($user);
        //return $user_id;
    }

    public static function bindTempAegister($user)
    {
        $user_id= Db::table(self::$bindTemp)->insertGetId($user);
        return $user_id;
    }

    //根据openid获取用户信息
    public static function getOpenid($openid)
    {
        $where = [
            't.openid' =>$openid,
            't.is_del' => 0,
            // 'w.type' =>1,
        ];
        $field='t.id,t.name,t.phone,t.icon,t.status,t.is_del';
        return Db::table(self::$table)
            ->alias('t')
            // ->join(self::$wechatTable . 'w', 'w.user_id = t.id', 'left')
            ->field($field)
            ->where($where)->find();
    }

    public static function clientPushFind($where, $field = "*"){
        return Db::table(self::$client_push)->field($field)->where($where)->find();
    }

    public static function clientPushInsert($param){
        if (!isset($param['create_time'])) {
            $param['create_time'] = CURR_TIME;
        }
        if (!isset($param['update_time'])) {
            $param['update_time'] = CURR_TIME;
        }
        return Db::table(self::$client_push)->insert($param);
    }

    public static function clientPushUpdate($where, $param){
        if (!isset($param['update_time'])) {
            $param['update_time'] = CURR_TIME;
        }
        return Db::table(self::$client_push)->where($where)->update($param);
    }

    public static function wechatGetOpenid($openid){
        $field='id,openid,unionid,access_token,refresh_token';
        return Db::table(self::$wechatTable)->field($field)->where('openid',$openid)->find();
    }
    //获微信信息
    public static function wechatInfo($id){
        return Db::table(self::$wechatTable)->find($id);
    }

    public static function wxInsert($data){
        $data['type'] = 1;
        $data['create_time'] = CURR_TIME;
        $data['update_time'] = CURR_TIME;
        return Db::table(self::$wechatTable)->insertGetId($data);
    }
    public static function wechatUpdate($id,$data){
        $data['update_time'] = CURR_TIME;
        return Db::table(self::$wechatTable)->where('id',$id)->update($data);
    }

    public static function bindingUser($user_id, $data){
        $pid = Db::table(self::$table)->where(['id'=>$user_id, 'is_del'=>0])->value('pid');
        if(!$pid){
            $pid = $user_id;
        }
        if($data['phone']){
            self::upUserInfo(['phone'=>$data['phone'], 'is_del'=>0], ['pid'=>$pid]);
        } else {
            return Db::table(self::$table)->where(['id'=>$data['phone'], 'is_del'=>0])->update(['pid'=>$pid]);
        }

    }
    public static function getPhone($phone)
    {
        $where['is_del'] = 0;
        $where['phone'] = $phone;
        $field='id,phone, name,icon,status,openid,unionid';
        return Db::table(self::$table)->field($field)->where($where)->find();
    }
    public static function add($user_info, $data, $is_send_sms = false){
        Db::startTrans();
        try {
            $bind_status = 1;
            $_data = $data;
            $curr_time = CURR_TIME;
            $data['addr'] = '';
            $data['province'] = '';
            $data['city_code']= '';
            $data['addr_info']= '';
            $data['ad_code']  = '';
            $data['create_time'] = $curr_time;
            $data['update_time'] = $curr_time;
            $bind_user_id = Db::table(self::$table)->insertGetId($data);
            if(!$bind_user_id) return false;
            self::wechatAdd(['user_id' => $bind_user_id, 'type' => self::USER_TPYE_USER]);
            // 删除未操作的被关联用户数据
            self::userBindDel(['user_id'=>$bind_user_id, 'bind_user_id'=>$user_info['id']]);
            // 用户关联数据
            if(!$_data['phone']) $bind_status = 2;
            $_data['bind_type']   = 1;
            $_data['bind_status'] = $bind_status;
            $_data['user_id']     = $user_info['id'];
            $_data['bind_user_id']= $bind_user_id;

            $user_info['bind_type']   = 2;
            $user_info['bind_status'] = $bind_status;
            $user_info['user_id']     = $bind_user_id;
            $user_info['bind_user_id']= $user_info['id'];
            unset($user_info['id']);
            self::userBindAdd($_data);
            self::userBindAdd($user_info);
            //$_data['user_id'] = $bind_user_id;
            //self::userBindTempInsert($_data);
            if($is_send_sms){
                $message['phone'] = substr($data["phone"], -4);
                $sms_code = UserSms::invitation($data['phone'], $message);
                if($sms_code->Code != "OK" || $sms_code->Message != "OK"){
                    Log::record($sms_code->Message, 'error');
                }
            }
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }

    public static function userBindInsert($user_info, $data){
        //var_dump($user_info); var_dump($data);die;
        Db::startTrans();
        try {
            //self::userBindDel(['user_id'=>$data['user_id'], 'bind_user_id'=>$data['bind_user_id']]);
            self::userBindDel(['user_id'=>$data['bind_user_id'], 'bind_user_id'=>$data['user_id']]);
            $bind_status = 1;
            $data['bind_type']  = 1;
            $data['bind_status']= $bind_status;
            $user_info['bind_type']  = 2;
            $user_info['bind_status']= $bind_status;
            self::userBindAdd($data);
            self::userBindAdd($user_info);
            //$data['user_id'] = $bind_user_id;
            //self::userBindTempInsert($data);
            $msg_title = "您的好友邀请你为全民健身关联用户";
            $msg_content = "手机尾号".$user_info['phone']."的用户邀请你成为关联好友，成为关联好友后可以共享彼此的会员卡哦。是否同意？";
            $ext = json_encode(["_NOTIFICATION_BAR_STYLE_"=>2, "iOS"=>"alipush/RelevanceUser/?bind_user_id=".$data['user_id'], 'android'=>"alipush/RelevanceUser", "bind_user_id"=>$data['user_id']]);
            Push::getInstance()->pushMsgAll($data['bind_user_id'], UsersModel::USER_TPYE_USER, $msg_title, $msg_content, $ext);
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }

    public static function wechatAdd($param){
        if (!isset($param['create_time'])) {
            $param['create_time'] = CURR_TIME;
        }
        if (!isset($param['update_time'])) {
            $param['update_time'] = CURR_TIME;
        }
        return Db::table(self::$wechatTable)->insert($param);
    }

    public static function userBindAdd($param){
        if(isset($param["id"])) unset($param["id"]);
        if (!isset($param['create_time'])) {
            $param['create_time'] = CURR_TIME;
        }
        if (!isset($param['update_time'])) {
            $param['update_time'] = CURR_TIME;
        }
        return Db::table(self::$userBind)->insert($param);
    }

    public static function userBindTempInsert($param){
        if (!isset($param['create_time'])) {
            $param['create_time'] = CURR_TIME;
        }
        if (!isset($param['update_time'])) {
            $param['update_time'] = CURR_TIME;
        }
        return Db::table(self::$userBindTemp)->insert($param);
    }

    public static function userBindTempDel($where){
        return Db::table(self::$userBindTemp)->where($where)->delete();
    }

    public static function userBindSelect($param, $fields = '*'){
        return Db::table(self::$userBind)->field($fields)->where($param)->find();
    }

    public static function userBindAll($param, $fields = '*'){
        return Db::table(self::$userBind)->field($fields)->where($param)->select();
    }

    public static function userBindOrFind($paramAnd, $paramOr = "", $fields = '*'){
        return Db::table(self::$userBind)
            ->field($fields)
            ->where($paramAnd)
            ->where(function($query) use($paramOr){
                $query->whereOr($paramOr);
            })->select();
    }


    public static function userBindIdColumn($param){
        return Db::table(self::$userBind)->where($param)->order("create_time ASC")->column('bind_user_id');
    }

    public static function userBindUpdate($where, $param){
        if (!isset($param['update_time'])) {
            $param['update_time'] = CURR_TIME;
        }
        return Db::table(self::$userBind)->where($where)->update($param);
    }

    public static function userBindUpAll($user_id, $bind_user_id, $new_bind_user_id){
        Db::startTrans();
        try {
            self::userBindUpdate(['user_id' => $user_id, 'bind_user_id' => $bind_user_id], ['bind_user_id' => $new_bind_user_id, 'fid'=>$bind_user_id]);
            self::userBindUpdate(['user_id' => $bind_user_id, 'bind_user_id' => $user_id], ['user_id' => $new_bind_user_id, 'fid'=>$bind_user_id]);
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }

    public static function userBindDel($where){
        return Db::table(self::$userBind)->where($where)->delete();
    }

    public static function upBindUserReduction($user_id, $bind_user_id, $fid){
        Db::startTrans();
        try {
            self::userBindUpdate(['user_id' => $user_id, 'bind_user_id' => $bind_user_id], ['bind_user_id' => $fid, 'bind_status'=>2, 'fid'=>0, 'phone'=>""]);
            self::userBindUpdate(['user_id' => $bind_user_id, 'bind_user_id' => $user_id], ['user_id' => $fid, 'bind_status'=>2, 'fid'=>0]);
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }

    public static function userBindUpdateEdit($user_id, $bind_user_id, $status, $fid, $phone, $remark){
        Db::startTrans();
        try {
            if($status){
                self::userBindUpdate(['user_id' => $user_id, 'bind_user_id' => $bind_user_id], ['bind_status' => 2]);
                self::userBindUpdate(['user_id' => $bind_user_id, 'bind_user_id' => $user_id], ['bind_status' => 2]);
                self::editTiYuGoUserDataAll($fid, $bind_user_id);
                $msg_title = "您邀请的好友已经同意关联";
                $msg_content = "你邀请的手机尾号为".$phone."的好友已经同意了您的邀请。快去看看吧";
            } else {
                if($fid){
                    self::upBindUserReduction($user_id, $bind_user_id, $fid);
                    $bind_user_id = $fid;
                    //self::userBindUpdate(['user_id' => $user_id, 'bind_user_id' => $bind_user_id], ['user_id' => $fid, 'bind_status'=>2, 'fid'=>0]);
                    //self::userBindUpdate(['user_id' => $bind_user_id, 'bind_user_id' => $user_id], ['bind_user_id' => $fid, 'bind_status'=>2, 'fid'=>0, 'phone'=>""]);
                } else {
                    self::userBindDel(['user_id' => $user_id, 'bind_user_id' => $bind_user_id]);
                    self::userBindUpdate(['user_id' => $bind_user_id, 'bind_user_id' => $user_id], ['bind_status' => 4]);
                }
                $msg_title = "您邀请的好友已经拒绝关联";
                $msg_content = "你邀请的手机尾号为".$phone."的好友已经拒绝了您的邀请。快去看看吧";
            }
            $ext = json_encode(["_NOTIFICATION_BAR_STYLE_"=>2, "iOS"=>"alipush/getUserInfo?bind_user_id=".$bind_user_id, 'android'=>"alipush/getUserInfo", "bind_user_id"=>$bind_user_id]);
            Push::getInstance()->pushMsgAll($bind_user_id, UsersModel::USER_TPYE_USER, $msg_title, $msg_content, $ext);
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }
    // 修改表中所有用户端 user 数据
    public static function editTiYuGoUserDataAll($user_id, $bind_user_id){
        Db::startTrans();
        try {
            $where['user_id'] = $user_id;
            $param['user_id'] = $bind_user_id;
            $param['update_time'] = CURR_TIME;
            Db::table(self::$balance)->where($where)->update($param);
            Db::table(self::$banner)->where($where)->update($param);
            Db::table(self::$orderTable)->where($where)->update($param);
            Db::table(self::$bill)->where($where)->update($param);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
        return true;
    }

    public static function upUserInfo($where, $param)
    {
        if(!$param) return false;
        if (!isset($param['update_time'])) {
            $param['update_time'] = CURR_TIME;
        }
        return Db::table(self::$table)->where($where)->update($param);
    }

    public static function checkPhone($phone)
    {
        return Db::table(self::$table)->where(['phone' => $phone])
            ->field('id,name,icon,is_del')->find();
    }

    public static function update($cach)
    {
        $cach['update_time'] = CURR_TIME;
        return Db::table(self::$table)->update($cach);

    }
//    public function wechatUpdate($id,$data){
//        $data['update_time'] = CURR_TIME;
//        return Db::table(self::$table)->where('id',$id)->update($data);
//    }
    public static function userUpdate($id,$data){
        $data['update_time'] = CURR_TIME;
        return Db::table(self::$table)->where('id',$id)->update($data);
    }
    public static function orderList($user_id,$status, $pages)
    {
        //$where['o.user_id'] = $user_id;
        $where['c.is_del'] = 0;
        $where['cc.is_del'] = 0;
        $where['s.is_del'] = 0;
        switch ($status) {
            case 1: //已预约 已付款
                $where['o.status'] = 3;
                break;
            case 2: //待付款
                $where['o.status'] = 1;
                break;
            case 3: //已完成
                $where['o.status'] = 5;
                break;
            case 4: //已取消
                $where['o.status'] = ['in', [6, 8]];
                break;
        }
        $field = 'o.id,o.course_id,o.coach_id,o.status as order_status,o.pid,t.name as user_name,o.user_id,o.field_id,o.pay_user_id,o.field_type_id,c.id as course_id,s.id as schedule_id,
        c.title as course_title, c.age, c.type_id,cc.title as coach_title,cc.phone';
        $info = Db::table(self::$orderTable)->alias('o')
            ->join(self::$courseName . ' c', 'o.course_id = c.id', 'left')
            ->join(self::$coachName . ' cc', 'o.coach_id = cc.id', 'left')
            ->join(self::$course_scheduleName . ' s', 'o.schedule_id = s.id', 'left')
            ->join(self::$table . ' t', 'o.pay_user_id = t.id', 'left')
            ->where($where)
            ->where(function($query) use($user_id){
                $query->whereOr('o.user_id', $user_id);
                $query->whereOr('o.pay_user_id', $user_id);
            })
            ->field($field)
            ->order('o.id desc')
            ->page($pages)
            //->fetchSql(true)
            ->select();
        return $info;
    }

    // 获取场地
    public static function getFieldById($field_id, $field = '*'){
        $where['f.is_del'] =0;
        $where['f.id'] = $field_id;
        return Db::table(self::$fieldName)->alias('f')->field($field)->where($where)->find();
    }

    // 获取自定义场馆
    public static function getCustomField($field_id, $field = '*'){
        $where['cu.id'] = $field_id;
        $where['cu.is_del'] =0;
        return Db::table(self::$fieldCustom)->alias('cu')->field($field)->where($where)->find();
    }

    public static function userList($where)
    {
        $where['is_del'] = 0;
        $field = 'id,user_id,pay_user_id';
        return Db::table(self::$orderTable)->where($where)->field($field)->select();
    }
    public static function orderListImg($course_id, $field = '*')
    {
        $where['coach_id'] = $course_id;
        $where['type'] = 5;
        $where['is_del'] = 0;
        return Db::table(self::$coach_certName)->where($where)->field($field)->find();
    }


    public static function getUserListInfo($user_id)
    {
        $where['is_del'] = UsersModel::STATUS_DEL;
        $field = 'id,user_id,pay_user_id';
        return Db::table(self::$orderTable)
            ->where($where)
            ->where(function($query) use($user_id){
                $query->whereOr('user_id', $user_id);
                $query->whereOr('pay_user_id', $user_id);
            })
            //->fetchSql(true)
            ->select();
    }

    public static function personImg($coach_id,$field = '*')
    {
        $where['is_del']=0;
        $where['type'] = 4;
        $where['coach_id'] =$coach_id;
       // print_r($where);die;
        return  Db::table(self::$coach_certName)->where($where)->field($field)->find();
    }



    public static function tkOrderList($order_id)
    {
        $where['order_id'] = $order_id;
        $where['is_del'] = 0;
        $field = 'FROM_UNIXTIME(date,  "%Y年%m月%d日") date';
        return  Db::table(self::$order_dateTable)->where($where)->field($field)->select();
    }

    public static function statusInfo($where)
    {
        $where['t.is_del'] = UsersModel::STATUS_DEL;
        $fields= 't.id,t.remark,t.birthday,t.phone,t.icon,u.user_id,u.bind_user_id,u.bind_status,u.bind_type';
        return Db::table(self::$table)
            ->alias('t')
           ->join(self::$user_bindTable . ' u', 'u.bind_user_id = t.id', 'left')
            ->where($where)->field($fields)->select();
    }

    public static function upDateId($where, $data)
    {
       unset($data['id']);
        $data['update_time'] = time();
        return Db::table(self::$bindTemp)->where($where)->update($data);
    }

    public static function userBindFind($where, $fields = "*")
    {
        return Db::table(self::$user_bindTable)->field($fields)->where($where)->find();

    }

    public static function bindPhoneUpdate($where, $data)
    {
        $data['update_time'] = time();
        $data['bind_status'] = 1;
        return Db::table(self::$user_bindTable)->where($where)->update($data);
    }


    public static function cancelUpdate($user_id,$bind_user_id)
    {
        $where['user_id'] =$user_id;
        $where['bind_user_id'] =$bind_user_id;
        return Db::table(self::$user_bindTable)
            ->alias('t')
            ->join(self::$user_bindTable . ' u', 'u.bind_user_id = t.user_id', 'left')
            ->where($where)
            ->update(['bind_status'=>3]);
    }

    public static function userInfoOld($where)
    {
        $where['t.is_del'] = UsersModel::STATUS_DEL;
        $fields= 't.id,t.name,t.remark,t.sex,t.height,t.weight,t.birthday,t.phone,t.icon,u.id as uid,u.user_id,u.bind_user_id,u.bind_status,u.bind_type';
        return Db::table(self::$table)
            ->alias('t')
            ->join(self::$user_bindTable . ' u', 'u.bind_user_id = t.id', 'left')
            ->where($where)->field($fields)->find();
    }

    public static function userInfo($bind_user_id, $field)
    {
        $where['t.id'] = $bind_user_id;
        $where['t.is_del'] = UsersModel::STATUS_DEL;
        $fields = $field ?: 't.id,t.name,t.remark,t.sex,t.height,t.weight,t.birthday,t.phone,t.icon';
        return Db::table(self::$table)
            ->alias('t')
           // ->join(self::$user_bindTable . ' u', 'u.bind_user_id = t.id', 'left')
            ->where($where)->field($fields)->find();
    }

    public static function bindUser($user_id){
        $where['user_id']=$user_id;
        $field = 'bind_user_id as id,height,weight,sex,name,remark,phone,icon,user_id,bind_user_id,birthday,bind_status,bind_type';
        return Db::table(self::$user_bindTable) ->alias('t')->where($where)->field($field)->select();
    }

    public static function bindUserInfo($user_id,$bind_user_id)
    {
        $where['u.user_id'] =$user_id;
        $where['u.bind_user_id'] =$bind_user_id;
        $field= 't.user_id as id,t.name,t.remark,t.sex,t.height,t.weight,t.birthday,t.phone,t.icon,u.id as uid,u.user_id,u.bind_user_id,u.bind_status,u.bind_type';
        return Db::table(self::$userBindTemp)
            ->alias('t')
            ->join(self::$user_bindTable . ' u', 'u.bind_user_id = t.user_id', 'left')
            ->where($where)->field($field)->find();
    }

    public static function upUserDel($user_id,$bind_user_id) {
        Db::startTrans();
        try {
            self::userBindDel(['user_id' => $user_id, 'bind_user_id' => $bind_user_id]);
            self::userBindDel(['user_id' => $bind_user_id, 'bind_user_id' => $user_id]);
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }

    }

    public static function bindUserStatus($where, $field){
        $field = $field ?: 'bind_user_id as id,id as uid,user_id,bind_user_id,name,remark,sex,height,weight,birthday,phone,icon,bind_status,bind_type';
        return Db::table(self::$user_bindTable)->where($where)->field($field)->find();
    }


    public static function userBindJoin($where, $field='*'){
        $where['t.is_del'] = UsersModel::STATUS_DEL;
        return Db::table(self::$table)
            ->alias('t')
            ->join(self::$user_bindTable . ' u', 'u.bind_user_id = t.id', 'left')
            ->field($field)
            ->where($where)
            ->find();
    }


    public static function bindUserId($where,$field='*'){
        return Db::table(self::$user_bindTable)->where($where)->field($field)->find();
    }

    public static function bindUpdate($user_id, $bind_user_id,$uid,$fid){
        Db::startTrans();
        try {
        self::userBindUpdate(['user_id' => $user_id, 'bind_user_id' => $bind_user_id],['bind_user_id' => $uid]);
        self::userBindUpdate(['user_id' => $bind_user_id, 'bind_user_id' => $user_id],['bind_user_id' => $fid]);
        Db::commit();
        return true;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }

    }

    public static function getMaInfos($where, $fields = '*')
    {
        $where['t.is_del'] = UsersModel::STATUS_DEL;
       // $where['t.id'] =$id;
        return Db::table(self::$table)
            ->alias('t')

            ->field($fields)
            ->where($where)
            // ->fetchSql(true)
            ->find();
    }


    public static function getMaInfo($where, $field = '*')
    {
        $where['is_del'] = UsersModel::STATUS_DEL;
        return Db::table(self::$table)
            ->field($field)
            ->where($where)
            ->find();
    }

    public static function getMaster($where, $field = '*')
    {
        $where['is_del'] = UsersModel::STATUS_DEL;
        return Db::table(self::$table)->field($field)->where($where)->find();
    }

    public static function getRelationInfos($where, $field = '*')
    {
        $where['is_del'] = UsersModel::STATUS_DEL;
        return Db::table(self::$table)->field($field)->where($where)->select();
    }

    public static function getRelationInfoss($where, $fields= '*')
    {
        //$where['is_del'] = UsersModel::STATUS_DEL; $userBindTemp $table
        return Db::table(self::$userBindTemp)
        ->alias('t')
       ->join(self::$user_bindTable . ' u', 'u.bind_user_id = t.user_id', 'left')
        ->field($fields)
        ->where($where)
     // ->fetchSql(true)
        ->select();
    }

    public static function getRelationInfo($where)
    {
        $fields= 'u.id,u.name,u.remark,u.sex,u.height,u.weight,u.birthday,u.phone,u.icon,u.bind_user_id,u.bind_status,u.bind_type';
       // $fields = 't.user_id as id,t.remark,t.name,t.phone,t.icon,t.birthday,u.id as uid,u.user_id,u.bind_user_id,bind_status,bind_type';

        return Db::table(self::$table)
            ->alias('t')
            ->join(self::$user_bindTable . ' u', 'u.bind_user_id = t.id', 'left')
            ->where($where)->field($fields)->find();
    }


    public static function info($user_id,$bind_user_id,$field = '*')
    {
        $where['u.user_id'] =$user_id;
        $where['u.bind_user_id'] =$bind_user_id;
        //return Db::table(self::$user_bindTable)->alias('u')->field($field)->where($where)->find();
        return Db::table(self::$table)
            ->alias('t')
            ->join(self::$user_bindTable . ' u', 'u.user_id = t.id', 'left')
            ->field($field)
            ->where($where)
          // ->fetchSql(true)
            ->find();
    }

    public static function getInfo($id)
    {
       // $where['t.id'] =$id;
        $where['t.is_del'] = UsersModel::STATUS_DEL;
        $field='t.id,t.bind_status,t.phone,u.bind_user_id';
        return Db::table(self::$table)
            ->alias('t')
            ->join(self::$user_bindTable . ' u', 'u.user_id = t.id', 'left')
            ->field($field)
            ->where($where)
            ->where(function($query) use($id){
                $query->whereOr('u.user_id', $id);
                $query->whereOr('u.bind_user_id', $id);
            })
            // ->fetchSql(true)
            ->select();
    }



    public static function getTotal($user_id,$status)
    {
        //$where['o.user_id'] = $user_id;
        $where['c.is_del'] = 0;
        $where['cc.is_del'] = 0;
        $where['s.is_del'] = 0;
        switch ($status) {
            case 1: //已预约
                $where['o.status'] = 3;
                break;
            case 2: //待付款
                $where['o.status'] = 1;
                break;
            case 3: //已完成
                $where['o.status'] = 5;
                break;
            case 4: //已取消
                $where['o.status'] = ['in', [6, 8]];
                break;
        }
        return Db::table(self::$orderTable)->alias('o')
            ->join(self::$courseName . ' c', 'o.course_id = c.id', 'left')
            //->join(self::$fieldName . ' f', 'o.field_id = f.id', 'left')
            ->join(self::$coachName . ' cc', 'o.coach_id = cc.id', 'left')
            ->join(self::$course_scheduleName . ' s', 'o.schedule_id = s.id', 'left')
            ->where($where)
            ->where(function($query) use($user_id){
                $query->whereOr('o.user_id', $user_id);
                $query->whereOr('o.pay_user_id', $user_id);
            })
            ->count();
    }


    public static function courseList($user_id,$beginToday,$endToday,$type)
    {
        //$where['o.user_id'] = $user_id;
        $where['da.date'] = array(array('EGT',$beginToday),array('ELT',$endToday),'AND');
        $where['o.is_del'] = 0;
        $where['o.status'] = ['in', [3, 5]];
        if($type==1){
            $field = 'o.id,o.user_id,t.name as user_name,o.pay_user_id,o.coach_id,o.course_id,o.status as order_status,o.field_type_id,o.field_id,o.pid,c.title,c.age,c.type_id,FROM_UNIXTIME(da.date,  "%m月%d日") start_time,da.time_nodes,cc.phone';
        }else{
            $field = 'FROM_UNIXTIME(da.date,  "%Y-%m-%d") start_time';
        }
        $list = Db::table(self::$orderTable)->alias('o')
            ->join(self::$courseName . ' c', 'o.course_id = c.id', 'left')
            ->join(self::$order_dateTable . ' da', 'o.id = da.order_id', 'left')
            ->join(self::$coachName . ' cc', 'o.coach_id = cc.id', 'left')
            ->join(self::$table . ' t', 'o.pay_user_id = t.id', 'left')
            ->where($where)
            ->where(function($query) use($user_id){
                $query->whereOr('o.user_id', $user_id);
                $query->whereOr('o.pay_user_id', $user_id);
            })
            ->field($field)
            ->order('o.id desc')
           // ->fetchSql(true)
            ->select();
        return $list;
    }


    public static function index($user_id,$id)
    {
        $where['o.user_id'] = $user_id;
       // $where['co.type'] = 5;
        $where['o.id'] = $id;
        $where['o.is_del'] = 0;
        $where['o.status'] = 3;
        $field = 'o.id,o.course_id,c.title,f.title as field_title,f.addr, cc.title as coach_title,cc.phone,FROM_UNIXTIME(da.date,  "%Y年%m月%d日") start_time,da.time_nodes';
        $list = Db::table(self::$orderTable)->alias('o')
            ->join(self::$courseName . ' c', 'o.course_id = c.id', 'left')
            //->join(self::$coach_certName . ' co', 'o.coach_id = co.coach_id', 'left')
            ->join(self::$fieldName . ' f', 'o.field_id = f.id', 'left')
            ->join(self::$coachName . ' cc', 'o.coach_id = cc.id', 'left')
            ->join(self::$order_dateTable . ' da', 'o.id = da.order_id', 'left')
            ->where($where)
            ->field($field)
            ->order('c.id desc')
            ->select();
        return $list;
    }


    public static function getCheckPhone($phone)
    {
        return Db::table(self::$table)->where(['phone' => $phone])
            ->field('id,phone,is_del')->find();
    }

    public static function upUserPhone($where, $param)
    {
        if (!isset($param['update_time'])) {
            $param['update_time'] = CURR_TIME;
        }
        return Db::table(self::$table)->where($where)->update($param);
    }


    public static function getUserGroupInfo($where, $fields = '*'){
        $where['is_del'] = UsersModel::STATUS_DEL;
        return Db::table(self::$table)->field($fields)->where($where)->select();
    }
    public static function getUserInfo($where, $fields = '*'){
        $where['is_del'] = UsersModel::STATUS_DEL;
        return Db::table(self::$table)->field($fields)->where($where)->find();
    }

    public static function getUserList($where){
        $where['is_del'] = UsersModel::STATUS_DEL;
        return Db::table(self::$table)->where($where)->column('id');
    }

    public static function getUserCount($where){
        $where['is_del'] = UsersModel::STATUS_DEL;
        return Db::table(self::$table)->where($where)->count();
    }

    public static function userBindCount($where){
        return Db::table(self::$userBind)->where($where)->count();
    }

    public static function upUserAndBindUserInfo($user_id, $bind_user_id, $data){
        Db::startTrans();
        try {
            //UsersModel::upUserInfo(['id'=>$user_id], $data);
            UsersModel::userBindUpdate(['user_id'=>$user_id, 'bind_user_id'=>$bind_user_id], $data);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
        return true;
    }

    public static function eidtBindUserInfo($user_id, $bind_user_id, $bind_user_phone_id, $data){
        Db::startTrans();
        try {
            //$bind_user_phone_id = UsersModel::getUserInfo(['phone'=>$data['phone']], 'id')['id'] ?: 0;
            if($bind_user_phone_id){
                UsersModel::userBindUpdate(['user_id'=>$user_id, 'bind_user_id'=>$bind_user_id], ['phone'=>$data['phone'], 'bind_user_id'=>$bind_user_phone_id, 'fid'=>$bind_user_id, 'bind_status'=>1]);
                UsersModel::userBindUpdate(['user_id'=>$bind_user_id, 'bind_user_id'=>$user_id], ['user_id'=>$bind_user_phone_id, 'fid'=>$bind_user_id, 'bind_status'=>1]);
            }else{
                UsersModel::userBindUpdate(['user_id'=>$user_id, 'bind_user_id'=>$bind_user_id], ['phone'=>$data['phone'], 'bind_status'=>1]);
                UsersModel::userBindUpdate(['user_id'=>$bind_user_id, 'bind_user_id'=>$user_id], ['bind_status'=>1]);
            }
            //UsersModel::upUserInfo(['id'=>$bind_user_id], $data);
            $user_phone = UsersModel::getUserInfo(['id'=>$user_id], 'phone')['phone'];
            $msg_title = "您的好友邀请你为全民健身关联用户";
            $msg_content = "手机尾号".$user_phone."的用户邀请你成为关联好友，成为关联好友后可以共享彼此的会员卡哦。是否同意？";
            //$ext = json_encode(['url'=>"alipush/RelevanceUser", "bind_user_id"=>$user_id]);
            $ext = json_encode(["_NOTIFICATION_BAR_STYLE_"=>2, "iOS"=>"alipush/RelevanceUser/?bind_user_id=".$user_id, 'android'=>"alipush/RelevanceUser", "bind_user_id"=>$user_id]);
            Push::getInstance()->pushMsgAll($bind_user_id, UsersModel::USER_TPYE_USER, $msg_title, $msg_content, $ext);
            Db::commit();
            return $bind_user_phone_id;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }

    public static function checkUserNameIsExists($pid, $name){
        $res = Db::table(self::$table)
            ->field('id')
            ->where(['name'=>$name, 'phone'=>''])
            ->where(function($query) use ($pid){
                $query->where(['id'=>$pid])
                    ->whereOr(['pid'=>$pid]);
            })->find();
        if($res && $res['id']){
            return false;
        }
        return true;
    }
*/
}