<?php
namespace app\user\controller;

use app\user\logic\QqLogic;
use app\user\logic\UserLogic;
use app\user\model\UsersModel;
use app\user\Logic\WechatLogic;
use app\common\logic\MsgLogic;
use app\common\sms\UserSms;
use app\user\logic\MsgLogic as UserMsgLogic;

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
        if(!$phone || !$password) return error_out('', '参数错误');
        if (!UserLogic::getInstance()->check_mobile($phone)) {
            return error_out('', UserLogic::USER_SMS_SEND);
        }
        if(!UserLogic::getInstance()->check_password($password)) return error_out("", UserLogic::PWD_FOORMAT);
        $userInfo = UsersModel::getInstance()->userFind(["phone"=>$phone], "id, password, status");
        if(!$userInfo) return error_out("", UserLogic::USER_NOT_EXISTS);
        if($userInfo["status"]) return error_out("", UserLogic::USER_STATUS);
        if($userInfo["password"] !== md5(config("user_login_prefix").$password)) return error_out('', UserLogic::USER_PWD_MSG);
        $user_token = UserLogic::getInstance()->getToken($userInfo["id"]);
        return success_out([
            'token' => $user_token,
            'phone' => $phone,
        ], UserLogic::LOGIN_SUCCESS);
    }

    // 发送短信
    public function sendCode()
    {
        $phone = $this->request->post('phone/s', "");
        if (!UserLogic::getInstance()->check_mobile($phone)) {
            return error_out('', UserLogic::USER_SMS_SEND);
        }
//        $code = 111111;
//        Cache::store('user')->set('mobile_code:' . $phone, $code, 300);
//        return success_out('', '发送成功');

        $code = rand(100000 , 999999);
        $cache_result = Cache::store('user')->set('mobile_code:' . $phone, $code, 300);
        if ($cache_result !== true) return error_out('',UserLogic::USER_SMS_FAIL);
        $templateParam  = ['code'=>$code];
        $response = UserSms::code($phone , $templateParam);
        if ($response->Code == 'OK') {
            Cache::store('user')->set('mobile_code:' . $phone, $code, 300);
            return success_out('', '发送成功');
        } elseif ($response->Code == 'isv.BUSINESS_LIMIT_CONTROL') {
            return error_out('', '当前账户频率操作过快 请稍后重试');
        } else {
            return error_out('', '服务器异常');
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
        $user_id = UsersModel::getInstance()->userFind(["phone"=>$phone], "id")["id"] ?: 0;
        if(!$user_id) return error_out("", UserLogic::USER_NOT_EXISTS);
        // 验证码
        $oldCode = Cache::store('user')->get('mobile_code:' . $phone);
        if(!$oldCode) return error_out('', UserLogic::REDIS_CODE_MSG);
        if ($oldCode != $code) return error_out('', UserLogic::CODE_MSG);
        // 密码
        if (!UserLogic::getInstance()->check_password($newPwd) || !UserLogic::getInstance()->check_password($confirmPwd) ) {
            return error_out('', UserLogic::PWD_FOORMAT);
        }
        if($newPwd !== $confirmPwd) return error_out("", UserLogic::PASSWORD_MSG);
        $result = UsersModel::getInstance()->userEdit(["id"=>$user_id], ["password"=>md5(config("user_login_prefix").$newPwd)]);
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
        // 验证码
        $oldCode = Cache::store('user')->get('mobile_code:' . $phone);
        if(!$oldCode) return error_out('', UserLogic::REDIS_CODE_MSG);
        if ($oldCode != $code) return error_out('', UserLogic::CODE_MSG);
        // 密码
        if (!UserLogic::getInstance()->check_password($newPwd) || !UserLogic::getInstance()->check_password($confirmPwd) ) {
            return error_out('', UserLogic::PWD_FOORMAT);
        }
        if($newPwd !== $confirmPwd) return error_out("", UserLogic::PASSWORD_MSG);
        $userInfo = UsersModel::getInstance()->userFind(["phone"=>$phone], "id, phone, register_status");
        if ($userInfo) {
            if($userInfo["register_status"] === 1) return error_out("", UserLogic::HAS_REGISTER);
            // 微信绑定未注册手机号时，默认注册用户
            $uid = UsersModel::getInstance()->userUpdate($userInfo["id"], ["register_status"=>1, "password"=>md5(config("user_login_prefix").$newPwd)]);
        } else {
            $user["phone"]    = $phone;
            $user["password"] = md5(config("user_login_prefix").$newPwd);
            $user["province"] = "";
            $user["city_code"]= "";
            $user["ad_code"]  = "";
            $user["register_status"]  = 1;
            $uid = UsersModel::getInstance()->userAdd($user);
        }
        if($uid){
            $user_token = UserLogic::getInstance()->getToken($uid);
            return success_out([
                'token' => $user_token,
                'phone' => $phone,
            ], MsgLogic::SUCCESS);
        } else {
            return error_out("", MsgLogic::SERVER_EXCEPTION);
        }
    }

    //退出登录
    public function out()
    {
        $user_id = UserLogic::getInstance()->checkToken();
        $result = UserLogic::getInstance()->delToken($user_id);
        //UserLogic::getInstance()->delDeviceId($user_id);
        if ($result === false)  return error_out('', MsgLogic::SERVER_EXCEPTION);
        return success_out('', UserLogic::USER_OUT);
    }

    // 微信登录
    public function wechat()
    {
        $code = request()->post('code' , '');
        if (!$code) return error_out('', UserLogic::WECHAT_CODE);
        $wx_token = WechatLogic::getInstance()->getToken($code);
        if (isset($wx_token['errcode'])) {
            return error_out('', $wx_token['errmsg']);
        }
        $access_token= $wx_token['access_token'];
        $refresh_token= $wx_token['refresh_token'];
        $openid = $wx_token['openid'];
        $unionid = isset($wx_token['unionid']) ? $wx_token['unionid'] : '';
        //验证openid是否已存在
        $user = UsersModel::getInstance()->userFind(["openid"=>$openid], "id, phone");
        if ($user) { //已存在
            if ($user['is_del'] != UsersModel::STATUS_DEL) return error_out('', UserLogic::USER_STATUS);
            $user_token = UserLogic::getInstance()->getToken($user['id']);
            $result['user_token'] = $user_token;
            $result['phone'] = $user['phone'];
            $result['wechat_id'] = 0;
            $result['status'] = $user['phone'] ? 1 : 0;  // 是否绑定手机号 1 已绑定  0 未绑定
        }else{
            //获取微信信息
            $user_info = WechatLogic::getInstance()->getUserInfo($wx_token['access_token'], $wx_token['openid']);
            if (isset($user_info['errcode'])){
                return error_out((object)array() , $user_info['errmsg']);
            }
            $wechat_info = UsersModel::getInstance()->wechatFind(["openid"=>$wx_token['openid']], "id, openid, unionid");
            $data['access_token'] =$access_token;
            $data['unionid'] = $unionid;
            $data['openid']  = $openid;
            $data['headimgurl'] = WechatLogic::getInstance()->downloadAvar($user_info['headimgurl']);
            $data['nickname'] = $user_info['nickname'];
            if (!$wechat_info){
                $wechat_id = UsersModel::getInstance()->wxInsert($data);
                if (!$wechat_id) return error_out('', MsgLogic::SERVER_EXCEPTION);
            }else{
                $wechat_id = $wechat_info['id'];
                $update_result = UsersModel::getInstance()->wechatUpdate(["id"=>$wechat_info['id']], $data);
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


    // QQ登录
    public function qq()
    {
        $code = QqLogic::getInstance()->getAuthCode();
        $code = request()->post('code' , '');
        if (!$code) return error_out('', UserLogic::QQ_CODE);
        $QQToken = QqLogic::getInstance()->getToken($code);
        if (isset($wx_token['errcode'])) {
            return error_out('', $QQToken['errmsg']);
        }
        $access_token= $QQToken['access_token']; // access_token
        // 获取openid
        $openID = QqLogic::getInstance()->getOpenID($access_token);
        $openid = $openID["openid"]; // 用户openid
        /*******************************************************************/

        //$refresh_token= $QQToken['refresh_token'];
        //$openid = $QQToken['openid'];
        //$unionid = isset($wx_token['unionid']) ? $QQToken['unionid'] : '';
        //验证openid是否已存在
        $user = UsersModel::getInstance()->userFind(["openid"=>$openid], "id, phone");
        if ($user) { //已存在
            if ($user['is_del'] != UsersModel::STATUS_DEL) return error_out('', UserLogic::USER_STATUS);
            $user_token = UserLogic::getInstance()->getToken($user['id']);
            $result['user_token'] = $user_token;
            $result['phone'] = $user['phone'];
            $result['wechat_id'] = 0;
            $result['status'] = $user['phone'] ? 1 : 0;  // 是否绑定手机号 1 已绑定  0 未绑定
        }else{
            //获取微信信息
            $user_info = QqLogic::getInstance()->getUserInfo($QQToken['access_token'], $QQToken['openid']);
            if (isset($user_info['errcode'])){
                return error_out((object)array() , $user_info['errmsg']);
            }
            $wechat_info = UsersModel::getInstance()->wechatFind(["openid"=>$QQToken['openid']], "id, openid, unionid");
            $data['access_token'] =$access_token;
            $data['unionid'] = $unionid;
            $data['openid']  = $openid;
            $data['headimgurl'] = WechatLogic::getInstance()->downloadAvar($user_info['headimgurl']);
            $data['nickname'] = $user_info['nickname'];
            if (!$wechat_info){
                $wechat_id = UsersModel::getInstance()->wxInsert($data);
                if (!$wechat_id) return error_out('', MsgLogic::SERVER_EXCEPTION);
            }else{
                $wechat_id = $wechat_info['id'];
                $update_result = UsersModel::getInstance()->wechatUpdate(["id"=>$wechat_info['id']], $data);
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
        if(!UserLogic::getInstance()->check_mobile($phone)) return error_out("", UserLogic::USER_PHONE_MSG);
        $wechat_info = UsersModel::getInstance()->wechatFind(["id"=>$wechat_id]);
        if (!$wechat_info) return error_out('', MsgLogic::PARAM_MSG);
        //验证码写好放开
        $oldCode = Cache::store('user')->get('mobile_code:' . $phone);
        if (!$oldCode) return error_out('', UserLogic::REDIS_CODE_MSG);
        if ($oldCode != $code) return error_out('', UserLogic::CODE_MSG);
        //验证phone是否已存在;
        $user = UsersModel::getInstance()->userFind(["phone"=>$phone], "id, openid, is_del");
        if($user){
            if ($user['openid']){
                return error_out('', UserLogic::WECHAT_BINDINGS);
            }else{
                if ($user['is_del'] != 0) return error_out('', UserLogic::USER_IS_DEL);
                try{
                    Db::startTrans();
                    $update['unionid'] = $wechat_info['unionid'] ?: "";
                    $update['openid']  = $wechat_info['openid'];
                    UsersModel::getInstance()->userEdit(["id"=>$user['id']], $update);
                    // 修改微信表关联
                    UsersModel::getInstance()->wechatUpdate(["id"=>$wechat_id], ["user_id"=>$user['id'], "type"=>UsersModel::USER_TYPE_USER]);
                    Db::commit();
                }catch (Exception $e){
                    Db::rollback();
                    return error_out((object)array() , $e->getMessage());
                }
                $user_token = UserLogic::getInstance()->getToken($user['id']);
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
            $parm['register_status']  = 0;  // 0未注册
            $parm['create_time'] = CURR_TIME;
            $parm['update_time'] = CURR_TIME;
            $user_id = UsersModel::getInstance()->userInsert($wechat_info["id"], $parm);
            if ($user_id) {
                $user_token = UserLogic::getInstance()->getToken($user_id);
                return success_out([
                    'token' => $user_token,
                    'phone'=>$parm['phone'],
                    //'icon' => $parm['icon'] ? config('img.domain') . $parm['icon'] : '',
                    'wechat_id' => 0,
                    'status' => 1,
                ], UserLogic::LOGIN_SUCCESS);
            } else {
                return error_out('', MsgLogic::SERVER_EXCEPTION);
            }
        }
    }

}