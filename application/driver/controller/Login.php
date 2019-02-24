<?php
namespace app\driver\controller;

use app\admin\model\UserModel;
use app\driver\logic\ZfbLogic;
use app\driver\model\DriverModel;
use app\driver\logic\WechatLogic;
use app\user\logic\UserLogic;
use app\driver\logic\DriverLogic;
use app\common\logic\MsgLogic;
use app\common\sms\DriverSms;

use app\user\model\DownloadModel;
use think\Cache;
use think\Db;
use think\Exception;
use think\exception\HttpException;
class Login extends Base
{

    // 登陆
    public function index(){
        $phone    = $this->request->post('phone/s', "");
        $password = $this->request->post('password/s', "");
        if(!$phone || !$password) return error_out('', MsgLogic::PARAM_MSG);
        if (!UserLogic::getInstance()->check_mobile($phone)) {
            return error_out('', UserLogic::USER_SMS_SEND);
        }
        if(!UserLogic::getInstance()->check_password($password)) return error_out("", UserLogic::PWD_FOORMAT);
        $userInfo = DriverModel::getInstance()->userFind(["phone"=>$phone], "id, password, status, is_register");
        if(!$userInfo) return error_out("", UserLogic::USER_NOT_EXISTS);
        if($userInfo["status"]) return error_out("", UserLogic::USER_STATUS);
        if($userInfo["password"] !== md5(config("user_login_prefix").$password)) return error_out('', UserLogic::USER_PWD_MSG);
        $user_token = DriverLogic::getInstance()->getToken($userInfo["id"]);
        return success_out([
            'token' => $user_token,
            'phone' => $phone,
            "is_register"=>$userInfo["is_register"]
        ], UserLogic::LOGIN_SUCCESS);
    }
    // 发送短信
    public function sendCode()
    {
        $phone = $this->request->post('phone/s', "");
        if (!UserLogic::getInstance()->check_mobile($phone)) {
            return error_out('', UserLogic::USER_SMS_SEND);
        }
        if (config("sms_verify_code") === true) {
            $code = rand(100000 , 999999);
            $cache_result = Cache::store('driver')->set('mobile_code:' . $phone, $code, 300);
            if ($cache_result !== true) return error_out('', DriverLogic::USER_SMS_FAIL);
            $templateParam  = ['code'=>$code];
            $response = DriverSms::code($phone , $templateParam);
            if ($response->Code == 'OK') {
                Cache::store('driver')->set('mobile_code:' . $phone, $code, 300);
                return success_out('', '发送成功');
            } elseif ($response->Code == 'isv.BUSINESS_LIMIT_CONTROL') {
                return error_out('', '当前账户频率操作过快 请稍后重试');
            } else {
                return error_out('', '服务器异常');
            }
        } else {
            $code = 111111;
            Cache::store('driver')->set('mobile_code:' . $phone, $code, 300);
            return success_out('', '发送成功');
        }
    }
    // 忘记 密码
    public function forgetPwd(){
        //$user_id = UserLogic::getInstance()->checkToken();
        $phone = $this->request->post('phone/s', "");
        $code  = $this->request->post('code/d', 0);
        $newPwd= $this->request->post('newPwd/s', "");
        $confirmPwd = $this->request->post('confirmPwd/s', "");
        if(!$phone || !$code || !$newPwd || !$confirmPwd) return error_out("", MsgLogic::PARAM_MSG);
        if (!UserLogic::getInstance()->check_mobile($phone)) {
            return error_out('', UserLogic::USER_SMS_SEND);
        }
        $user_id = DriverModel::getInstance()->userFind(["phone"=>$phone], "id")["id"] ?: 0;
        if(!$user_id) return error_out("", UserLogic::USER_NOT_EXISTS);
        // 验证码
        $oldCode = Cache::store('driver')->get('mobile_code:' . $phone);
        if(!$oldCode) return error_out('', UserLogic::REDIS_CODE_MSG);
        if ($oldCode != $code) return error_out('', UserLogic::CODE_MSG);
        // 密码
        if (!UserLogic::getInstance()->check_password($newPwd) || !UserLogic::getInstance()->check_password($confirmPwd) ) {
            return error_out('', UserLogic::PWD_FOORMAT);
        }
        if($newPwd !== $confirmPwd) return error_out("", UserLogic::PASSWORD_MSG);
        $result = DriverModel::getInstance()->userEdit(["id"=>$user_id], ["passwrod"=>md5(config("user_login_prefix").$newPwd)]);
        if($result === false) return error_out("", MsgLogic::SERVER_EXCEPTION);
        return success_out("", MsgLogic::EDIT_SUCCESS);
    }

    // 注册
    public function register(){
        $phone = $this->request->post('phone/s', "");
        $code  = $this->request->post('code/d', 0);
        $newPwd= $this->request->post('newPwd/s', "");
        $confirmPwd = $this->request->post('confirmPwd/s', "");
        if(!$phone || !$code || !$newPwd || !$confirmPwd) return error_out("", MsgLogic::PARAM_MSG);
        if (!UserLogic::getInstance()->check_mobile($phone)) {
            return error_out('', UserLogic::USER_SMS_SEND);
        }
        //$user_id = DriverModel::getInstance()->userFind(["phone"=>$phone])["id"] ?: "";
        //if($user_id) return error_out("", UserLogic::HAS_REGISTER);
        // 验证码
        $oldCode = Cache::store('driver')->get('mobile_code:' . $phone);
        if(!$oldCode) return error_out('', UserLogic::REDIS_CODE_MSG);
        if ($oldCode != $code) return error_out('', UserLogic::CODE_MSG);
        // 密码
        if (!UserLogic::getInstance()->check_password($newPwd) || !UserLogic::getInstance()->check_password($confirmPwd) ) {
            return error_out('', UserLogic::PWD_FOORMAT);
        }
        if($newPwd !== $confirmPwd) return error_out("", UserLogic::PASSWORD_MSG);
        $userInfo = DriverModel::getInstance()->userFind(["phone"=>$phone], "id, phone, register_status");
        if ($userInfo) {
            if($userInfo["register_status"] === 1) return error_out("", UserLogic::HAS_REGISTER);
            // 微信绑定未注册手机号时，默认注册用户
            $uid = DriverModel::getInstance()->userUpdate($userInfo["id"], ["register_status"=>1, "password"=>md5(config("user_login_prefix").$newPwd)]);
        } else {
            $user["phone"]    = $phone;
            $user["password"] = md5(config("user_login_prefix").$newPwd);
            $user["id_number"] = "";
            $user["contacts_phone"] = "";
            $user["audit_status"] = 1;
            $user["car_number"] = "";
            $user["province"] = "";
            $user["city_code"]= "";
            $user["ad_code"]  = "";
            $user["register_status"]  = 1;
            $uid = DriverModel::getInstance()->userAdd($user);
        }
        if($uid){
            $user_token = DriverLogic::getInstance()->getToken($uid);
            return success_out([
                'token' => $user_token,
                'phone' => $phone
            ], MsgLogic::REG_SUCCESS);
        } else {
            return error_out("", MsgLogic::SERVER_EXCEPTION);
        }
    }

    //退出登录
    public function out()
    {
        $user_id = DriverLogic::getInstance()->checkToken();
        $result = DriverLogic::getInstance()->delToken($user_id);
        //UserLogic::getInstance()->delDeviceId($user_id);
        if ($result === false)  return error_out('', MsgLogic::SERVER_EXCEPTION);
        return success_out('', UserLogic::USER_OUT);
    }

    // 微信登录
    public function wechat()
    {
        $code = request()->post('code' , '');
        if (!$code) return error_out('', DriverLogic::WECHAT_CODE);
        $wx_token = WechatLogic::getInstance()->getToken($code);

//        $wx_token["access_token"] = "16_NsrGS3GC5o9A0ZNKImLrxk4SWvBVMLGo6GmFhWllHgg7tClU4KsdeXAE2SbY5UzENddImnYJOl7oZkUy0Pkm277Wxhn1OeNZcqY7YthdqZs";
//        $wx_token["expires_in"] = 7200;
//        $wx_token["refresh_token"] = "16_mkORWIJkxdVFSgfqzxy81E5LM93_ytbKesBBRLiD1lbxAqax-lGZ0V1k4p01KJvsFJgFDNODD2CKQ1mq-XyqpxXZRSLm6fggR3kRPBMbRtY";
//        $wx_token["openid"] = "oLnON5hbziox7zW4Am5SJr5ZC57E";
//        $wx_token["scope"] = "snsapi_userinfo";
//        $wx_token["unionid"] = "onxbd54zDp2zaYCvy7Jb25G5ytzw";

        if (isset($wx_token['errcode'])) {
            return error_out('', $wx_token['errmsg']);
        }
        $access_token= $wx_token['access_token'];
        $refresh_token= $wx_token['refresh_token'];
        $openid = $wx_token['openid'];
        $unionid = isset($wx_token['unionid']) ? $wx_token['unionid'] : '';
        //验证openid是否已存在
        $user = DriverModel::getInstance()->userFind(["openid"=>$openid], "id, phone, is_del");
        if ($user) { //已存在
            if ($user['is_del'] != DriverModel::STATUS_DEL) return error_out('', DriverLogic::USER_STATUS);
            $user_token = DriverLogic::getInstance()->getToken($user['id']);
            $result['token'] = $user_token;
            $result['phone'] = $user['phone'];
            $result['wechat_id'] = 0;
            $result['status'] = $user['phone'] ? 1 : 0;  // 是否绑定手机号 1 已绑定  0 未绑定
        }else{
            //获取微信信息
            $user_info = WechatLogic::getInstance()->getUserInfo($wx_token['access_token'],$wx_token['openid']);
            if (isset($user_info['errcode'])){
                return error_out((object)array() , $user_info['errmsg']);
            }
            $wechat_info = DriverModel::getInstance()->wechatFind(["openid"=>$wx_token['openid']], "id, openid, unionid");
            $data['access_token'] =$access_token;
            $data['unionid'] = $unionid;
            $data['openid']  = $openid;
            $data['headimgurl'] = WechatLogic::getInstance()->downloadAvar($user_info['headimgurl']);
            $data['nickname'] = $user_info['nickname'];
            $data['third_party_type'] = DriverModel::WX_THIRD_PARTY_TYPE;
            if (!$wechat_info){
                $wechat_id = DriverModel::getInstance()->wxInsert($data);
                if (!$wechat_id) return error_out('', MsgLogic::SERVER_EXCEPTION);
            }else{
                $wechat_id = $wechat_info['id'];
                $update_result = DriverModel::getInstance()->wechatUpdate(["id"=>$wechat_info['id']], $data);
                if (!$update_result) return error_out('', MsgLogic::SERVER_EXCEPTION);
            }
            $result = [
                'token' => '',
                'phone' => '',
                'wechat_id' =>(int) $wechat_id,
                'status' => 0
            ];
        }
        return success_out($result , MsgLogic::SUCCESS);
    }

    // 微信登陆绑定手机号
    public function bindingPhone()
    {
        $phone = $this->request->post('phone/s', '');
        $code = $this->request->post('code/d', 0);
        $wechat_id = $this->request->post('wechat_id/d', 0);
        if(!$phone || !$code || !$wechat_id) return error_out("", MsgLogic::PARAM_MSG);
        $wechat_info = DriverModel::getInstance()->wechatFind(["id"=>$wechat_id]);
        if (!$wechat_info) return error_out('', MsgLogic::PARAM_MSG);
        //验证码写好放开
        $oldCode = Cache::store('driver')->get('mobile_code:' . $phone);
        if (!$oldCode) return error_out('', DriverLogic::REDIS_CODE_MSG);
        if ($oldCode != $code) return error_out('', DriverLogic::CODE_MSG);
        //验证phone是否已存在;
        $user = DriverModel::getInstance()->userFind(["phone"=>$phone], "id, openid, is_del");
        if($user){
            if ($user['openid']){
                return error_out('', DriverLogic::WECHAT_BINDINGS);
            }else{
                if ($user['is_del'] != 0) return error_out('', DriverLogic::USER_IS_DEL);
                try{
                    Db::startTrans();
                    $update['unionid'] = $wechat_info['unionid'] ?: "";
                    $update['openid']  = $wechat_info['openid'];
                    DriverModel::getInstance()->userEdit(["id"=>$user['id']], $update);
                    // 修改微信表关联
                    DriverModel::getInstance()->wechatUpdate(["id"=>$wechat_id], ["user_id"=>$user['id'], "type"=>DriverModel::USER_TYPE_USER]);
                    Db::commit();
                }catch (Exception $e){
                    Db::rollback();
                    return error_out((object)array() , $e->getMessage());
                }
                $user_token = DriverLogic::getInstance()->getToken($user['id']);
                //$icon = $user['icon'] ? $user['icon']: WechatLogic::getInstance()->downloadAvar($wechat_info['headimgurl']);
                return success_out([
                    'token' =>$user_token,
                    'phone' =>$phone,
                    'wechat_id' =>0,
                    'status' => 1,
                ], UserLogic::WX_BIND_SUCCESS);
            }
        }else{
            $parm['phone']  = $phone;
            $parm['name']   = $wechat_info["nickname"];
            $parm['openid'] = $wechat_info['openid'];
            $parm['unionid']= $wechat_info['unionid'];
            $parm['icon']   = WechatLogic::getInstance()->downloadAvar($wechat_info['headimgurl']);
            $parm["sex"]    = $wechat_info["sex"];
            $parm['province'] = '';
            $parm['city_code']= '';
            $parm['ad_code']  = '';
            $parm["id_number"]= '';
            $parm["password"] = md5(config("user_login_prefix")."111111");
            $parm['register_status']  = 0;  // 0未注册
            $parm['create_time'] = CURR_TIME;
            $parm['update_time'] = CURR_TIME;
            $user_id = DriverModel::getInstance()->userWechatFind($wechat_info["id"], $parm);
            if ($user_id) {
                $user_token = DriverLogic::getInstance()->getToken($user_id);
                return success_out([
                    'token' => $user_token,
                    'phone'=>$parm['phone'],
                    //'icon' => $parm['icon'] ? config('img.domain') . $parm['icon'] : '',
                    'wechat_id' => 0,
                    'status' => 1,
                ], DriverLogic::LOGIN_SUCCESS);
            } else {
                return error_out('', MsgLogic::SERVER_EXCEPTION);
            }
        }
    }

    // 微信授权
    public function wechatAuth()
    {
        $user_id = DriverLogic::getInstance()->checkToken();
        $code = $this->request->post('code/s', "");
        if (!$code) return error_out('', DriverLogic::WECHAT_CODE);

//        $access_result["access_token"] = "16_NsrGS3GC5o9A0ZNKImLrxk4SWvBVMLGo6GmFhWllHgg7tClU4KsdeXAE2SbY5UzENddImnYJOl7oZkUy0Pkm277Wxhn1OeNZcqY7YthdqZs";
//        $access_result["expires_in"] = 7200;
//        $access_result["refresh_token"] = "16_mkORWIJkxdVFSgfqzxy81E5LM93_ytbKesBBRLiD1lbxAqax-lGZ0V1k4p01KJvsFJgFDNODD2CKQ1mq-XyqpxXZRSLm6fggR3kRPBMbRtY";
//        $access_result["openid"] = "oLnON5hbziox7zW4Am5SJr5ZC57E";
//        $access_result["scope"] = "snsapi_userinfo";
//        $access_result["unionid"] = "onxbd54zDp2zaYCvy7Jb25G5ytzw";

        $userInfo = DriverModel::getInstance()->userFind(["id"=>$user_id], "openid");
        if (!empty($userInfo['openid'])){
            return error_out("", DriverLogic::WECHAT_CODE_EXISTS);
        }
        $access_result = WechatLogic::getInstance()->getToken($code);
        if (isset($access_result['errcode'])){
            return error_out("", $access_result['errmsg']);
        }
        $result = DriverModel::getInstance()->wechatAuth($user_id, $access_result);
        if ($result === true){
            return success_out('','绑定微信成功');
        }else{
            return error_out('','绑定微信失败');
        }
    }

/*    // 支付宝授权
    public function zfbAuth()
    {
        $user_id = DriverLogic::getInstance()->checkToken();
        $code = $this->request->post('code/s', "");
        if (!$code) return error_out('', DriverLogic::WECHAT_CODE);

//        $access_result["access_token"] = "16_NsrGS3GC5o9A0ZNKImLrxk4SWvBVMLGo6GmFhWllHgg7tClU4KsdeXAE2SbY5UzENddImnYJOl7oZkUy0Pkm277Wxhn1OeNZcqY7YthdqZs";
//        $access_result["expires_in"] = 7200;
//        $access_result["refresh_token"] = "16_mkORWIJkxdVFSgfqzxy81E5LM93_ytbKesBBRLiD1lbxAqax-lGZ0V1k4p01KJvsFJgFDNODD2CKQ1mq-XyqpxXZRSLm6fggR3kRPBMbRtY";
//        $access_result["openid"] = "oLnON5hbziox7zW4Am5SJr5ZC57E";
//        $access_result["scope"] = "snsapi_userinfo";
//        $access_result["unionid"] = "onxbd54zDp2zaYCvy7Jb25G5ytzw";

        $userInfo = DriverModel::getInstance()->userFind(["id"=>$user_id], "openid");
        if (!empty($userInfo['openid'])){
            return error_out("", DriverLogic::WECHAT_CODE_EXISTS);
        }
        $access_result = WechatLogic::getInstance()->getToken($code);
        if (isset($access_result['errcode'])){
            return error_out("", $access_result['errmsg']);
        }
        $result = DriverModel::getInstance()->wechatAuth($user_id, $access_result);
        if ($result === true){
            return success_out('','绑定微信成功');
        }else{
            return error_out('','绑定微信失败');
        }
    }*/

    // 获取支付宝用户信息
    public function zfbAuth(){
        $user_id = DriverLogic::getInstance()->checkToken();
        $code = $this->request->post('code/s', "");
        if (!$code) return error_out('', DriverLogic::ZFB_AUTH_CODE);
        $access_token = ZfbLogic::getInstance()->alipayToken($code);
        if ($access_token === false) return error_out("", "获取token失败");
        $userInfo = ZfbLogic::getInstance()->alipayUserInfo($access_token);
        if ($userInfo === false) return error_out("", "授权失败");
//        $userInfo["user_id"] = "2088102104794936";
//        $userInfo["avatar"] = "http://tfsimg.alipay.com/images/partner/T1uIxXXbpXXXXXXXX";
//        $userInfo["province"] = "安徽省";
//        $userInfo["city"] = "安庆";
//        $userInfo["nick_name"] = "支付宝小二";
//        $userInfo["is_student_certified"] = "T";
//        $userInfo["user_type"] = "1";
//        $userInfo["user_status"] = "T";
//        $userInfo["is_certified"] = "T";
//        $userInfo["gender"] = "F";
        // 存入微信表和绑定用户表数据
        $result = DriverModel::getInstance()->zfbAuth($user_id, $userInfo);
        if ($result === true){
            return success_out('','绑定支付宝成功');
        }else{
            return error_out('','绑定支付宝失败');
        }
    }


    // 保存push 数据
    public function clientPushAccount(){
        $driver_id = DriverLogic::getInstance()->checkToken();
        $device_type = request()->post('device_type/d' , 0);      // 设备类型 1 ios 2 android
        $device_number = request()->post('device_number/s' , ""); // 设备号
        $service_type = request()->post('service_type/d' , 1);    // 服务器 0测试 1正式 【默认 0】 Android 必传1
        if(!$device_number) return error_out('', '参数错误');
        $where['user_id']   = $driver_id;
        $where['user_type'] = UsersModel::USER_TPYE_USER;
        $data['device_type']  = $device_type;
        $data['device_number']= $device_number;
        $data['service_type'] = $service_type;
        $user_push = DriverModel::getInstance()->clientPushFind(['user_id'=>$driver_id, 'user_type'=>DriverModel::PUTH_DRIVER_TPYE], 'device_type, device_number');
        if(!$user_push){
            $data['user_id']   = $driver_id;
            $data['user_type'] = DriverModel::PUTH_DRIVER_TPYE;
            $request = DriverModel::getInstance()->clientPushInsert($data);
            if($request === false)  return error_out('', "服务器异常");
        }
        if($user_push['device_type'] != $device_type || $user_push['device_number'] != $device_number){
            $request = DriverModel::getInstance()->clientPushUpdate(['user_id'=>$driver_id, 'user_type'=>DriverModel::PUTH_DRIVER_TPYE], $data);
            if($request === false)  return error_out('', "服务器异常");
        }
        return success_out("", "添加成功");
    }

}